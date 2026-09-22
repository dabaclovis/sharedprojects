<?php

namespace App\Livewire\Users;

use App\Enums\PostCategory;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('My articles')]
class Articles extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Locked]
    public ?int $postId = null;

    public bool $showEditor = false;

    public string $search = '';

    public string $title = '';

    public string $excerpt = '';

    public string $content = '';

    public string $category = '';

    public string $icon = '';

    public string $status = 'draft';

    public function boot(): void
    {
        abort_unless(Auth::check() && Auth::user()->status === 'active', 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->cancel();
        $this->showEditor = true;
    }

    public function edit(int $id): void
    {
        $post = Auth::user()->posts()->findOrFail($id);
        $this->cancel();
        $this->postId = $post->id;
        foreach (['title', 'excerpt', 'content', 'category', 'icon', 'status'] as $field) {
            $this->{$field} = $post->{$field} ?? '';
        }
        $this->showEditor = true;
    }

    public function cancel(): void
    {
        $this->reset('postId', 'showEditor', 'title', 'excerpt', 'content', 'category', 'icon', 'status');
        $this->resetValidation();
    }

    public function save(): void
    {
        $post = $this->postId ? Auth::user()->posts()->findOrFail($this->postId) : new Post;
        $this->title = trim($this->title);
        $this->content = trim($this->content);
        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'string', 'max:100000'],
            'category' => ['nullable', Rule::enum(PostCategory::class)],
            'icon' => ['nullable', Rule::in(['fa-file-lines', 'fa-lightbulb', 'fa-comments', 'fa-seedling'])],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);
        $post->fill(collect($data)->except('status')->all());
        if (! $post->exists) {
            $post->slug = Str::substr(Str::slug($this->title) ?: 'article', 0, 200).'-'.Str::uuid();
            $post->author()->associate(Auth::user());
            $post->postsable()->associate(Auth::user());
        }
        // New articles and revisions must be reviewed before becoming public.
        $post->status = 'draft';
        $post->published_at = null;
        $post->save();
        $this->cancel();
        $this->resetPage();
        session()->flash('articleStatus', 'Article saved as a draft awaiting admin approval.');
    }

    public function delete(int $id): void
    {
        Auth::user()->posts()->findOrFail($id)->delete();
        $this->cancel();
        $this->resetPage();
        session()->flash('articleStatus', 'Article deleted.');
    }

    public function render()
    {
        return view('livewire.users.articles', [
            'posts' => Auth::user()->posts()
                ->with('remarks.admin:id,name')
                ->when(trim($this->search) !== '', fn ($query) => $query->where('title', 'like', '%'.trim($this->search).'%'))
                ->latest('updated_at')->orderByDesc('id')->paginate(10),
        ]);
    }
}
