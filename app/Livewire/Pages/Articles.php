<?php

namespace App\Livewire\Pages;

use App\Enums\PostCategory;
use App\Models\Post;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'Community Articles, Ideas and Stories | Brotherfall',
        'description' => 'Read useful community-written articles, fresh ideas and practical stories across business, technology and everyday life.',
        'keywords' => 'community articles, practical ideas, stories, business articles, technology articles',
    ]
)]
class Articles extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    #[Locked]
    public string $category = '';

    public function mount(?string $category = null): void
    {
        if ($category !== null) {
            $match = collect(PostCategory::cases())->first(fn ($item) => strtolower($item->value) === $category);
            abort_unless($match, 404);
            $this->category = $match->value;
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);
        $search = mb_substr($search, 0, 200);
        $posts = Post::published()->with('author:id,name')
            ->select(['id', 'author_id', 'title', 'slug', 'excerpt', 'content', 'category', 'icon', 'published_at'])
            ->when($this->category !== '', fn ($query) => $query->whereRaw('LOWER(category) = ?', [strtolower($this->category)]))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('excerpt', 'like', '%'.$search.'%')
                        ->orWhere('category', 'like', '%'.$search.'%')
                        ->orWhereHas('author', fn ($author) => $author->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc('published_at')->orderByDesc('id')->paginate(3);

        return view('livewire.pages.articles', ['posts' => $posts])->layoutData([
            'title' => $this->category ? $this->category.' Articles and Guides | Brotherfall' : config('seo.routes.pages.articles.title'),
            'description' => $this->category ? 'Explore practical '.strtolower($this->category).' guides, tutorials and ideas from the Brotherfall community.' : config('seo.defaults.description'),
        ]);
    }
}
