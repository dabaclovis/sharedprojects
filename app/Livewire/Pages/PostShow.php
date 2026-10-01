<?php

namespace App\Livewire\Pages;

use App\Models\Post;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PostShow extends Component
{
    #[Locked]
    public string $slug;

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }

    public function render()
    {
        $post = Post::published()->with('author')->where('slug', $this->slug)->firstOrFail();

        return view('livewire.pages.post-show', ['post' => $post])
            ->layoutData([
                'description' => $post->excerpt ?: 'Read '.$post->title.' and discover more community stories and practical ideas.',
                'keywords' => collect([$post->category, 'community article', 'stories', 'ideas'])->filter()->implode(', '),
            ])
            ->title(ucfirst($post->title));
    }
}
