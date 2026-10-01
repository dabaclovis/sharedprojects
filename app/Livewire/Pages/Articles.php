<?php

namespace App\Livewire\Pages;

use App\Models\Post;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'Community Articles, Ideas and Stories | CD',
        'description' => 'Read useful community-written articles, fresh ideas and practical stories across business, technology and everyday life.',
        'keywords' => 'community articles, practical ideas, stories, business articles, technology articles',
    ]
)]
class Articles extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);
        $search = mb_substr($search, 0, 200);
        $posts = Post::published()->with('author:id,name')
            ->select(['id', 'author_id', 'title', 'slug', 'excerpt', 'category', 'icon', 'published_at'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('excerpt', 'like', '%'.$search.'%')
                        ->orWhere('category', 'like', '%'.$search.'%')
                        ->orWhereHas('author', fn ($author) => $author->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc('published_at')->orderByDesc('id')->paginate(3);

        return view('livewire.pages.articles', ['posts' => $posts]);
    }
}
