<?php

namespace App\Livewire\Pages;

use App\Models\Post;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class PostShow extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Locked]
    public string $slug;

    public string $newComment = '';

    public ?int $replyToId = null;

    public string $replyBody = '';

    public function mount(string $slug): void
    {
        $this->slug = $slug;

    }

    public function submitComment(): void
    {
        $this->ensureActiveUser();
        $this->newComment = trim($this->newComment);
        $data = $this->validate(['newComment' => ['required', 'string', 'max:3000']]);
        $post = $this->visiblePost();
        $post->comments()->create(['user_id' => auth()->id(), 'body' => $data['newComment']]);
        $this->reset('newComment');
        $this->resetPage('commentsPage');
        session()->flash('commentStatus', 'Comment posted.');
    }

    public function openReply(int $commentId): void
    {
        $this->ensureActiveUser();
        $this->visiblePost()->comments()->whereNull('parent_id')->findOrFail($commentId);
        $this->replyToId = $commentId;
        $this->replyBody = '';
        $this->resetValidation();
    }

    public function cancelReply(): void
    {
        $this->reset('replyToId', 'replyBody');
        $this->resetValidation();
    }

    public function submitReply(): void
    {
        $this->ensureActiveUser();
        $this->replyBody = trim($this->replyBody);
        $data = $this->validate(['replyBody' => ['required', 'string', 'max:3000']]);
        $post = $this->visiblePost();
        $parent = $post->comments()->whereNull('parent_id')->findOrFail($this->replyToId);
        $post->comments()->create([
            'user_id' => auth()->id(),
            'parent_id' => $parent->id,
            'body' => trim($data['replyBody']),
        ]);
        $this->cancelReply();
        $this->resetPage('commentsPage');
        session()->flash('commentStatus', 'Reply posted.');
    }

    private function visiblePost(): Post
    {
        return Post::published()->where('slug', $this->slug)->firstOrFail();
    }

    private function ensureActiveUser(): void
    {
        abort_unless(auth()->check() && auth()->user()->status === 'active', 403);
    }

    public function render()
    {
        $post = Post::published()->with('author')->where('slug', $this->slug)->firstOrFail();
        $comments = $post->comments()->whereNull('parent_id')
            ->with(['user:id,name,username', 'replies.user:id,name,username'])
            ->latest('id')->paginate(10, ['*'], 'commentsPage');

        return view('livewire.pages.post-show', ['post' => $post, 'comments' => $comments, 'relatedPosts' => Post::published()->where('id', '!=', $post->id)->whereRaw('LOWER(category) = ?', [strtolower($post->category ?? '')])->latest('published_at')->limit(3)->get()])
            ->layoutData([
                'canonical' => route('pages.postshow', $post->slug),
                'description' => $post->meta_description ?: $post->excerpt ?: 'Read '.$post->title.' and discover more community stories and practical ideas.',
                'keywords' => collect([$post->category, 'community article', 'stories', 'ideas'])->filter()->implode(', '),
            ])
            ->title($post->seo_title ?: ucfirst($post->title));
    }
}
