<?php

namespace App\Livewire\Pages;

use App\Models\Post;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'Discover stories ~ my app',
        'description' => 'Discover the latest stories, ideas, and updates from our community.',
        'keywords' => 'posts, stories, community',
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
        $posts = Post::published()->with('author')
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
