<?php

namespace Tests\Feature;

use App\Livewire\Pages\Articles;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PostsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['Every great story starts with a hello', 'Small habits, meaningful progress', 'Building something worth sharing'] as $index => $title) {
            $author = User::factory()->create(['name' => $index === 1 ? 'Alex Morgan' : 'Joe Doe']);
            $post = new Post(['title' => $title, 'slug' => 'story-'.$index, 'content' => 'Article text']);
            $post->author()->associate($author);
            $post->postsable()->associate($author);
            $post->status = 'published';
            $post->published_at = now()->subDay();
            $post->save();
        }
    }

    public function test_page_displays_database_posts_and_pagination(): void
    {
        $this->get(route('pages.articles'))->assertOk()
            ->assertSee('Good stories. Fresh perspectives.')
            ->assertSee('Every great story starts with a hello')
            ->assertSee('Small habits, meaningful progress')
            ->assertSee('Building something worth sharing')
            ->assertSee('Post pagination');

        Livewire::test(Articles::class)->assertViewHas('posts', fn ($posts) => $posts->total() === 3 && $posts->perPage() === 3 && $posts->currentPage() === 1
        );
    }

    public function test_homepage_displays_services_instead_of_the_post_list(): void
    {
        $this->get(route('pages.index'))->assertOk()
            ->assertSee('Good stories. Fresh perspectives.')
            ->assertSee('Post management')->assertSee('Affiliate link posting')
            ->assertSee('Event scheduling')->assertSee(route('pages.articles'))
            ->assertDontSee('Every great story starts with a hello')
            ->assertDontSee('wire:model.live.debounce.300ms="search"', false);
    }

    public function test_live_search_filters_posts_and_resets_pagination(): void
    {
        Livewire::test(Articles::class)
            ->call('setPage', 2)
            ->set('search', '  ALEX  ')
            ->assertSet('paginators.page', 1)
            ->assertSee('Small habits, meaningful progress')
            ->assertDontSee('Every great story starts with a hello')
            ->assertViewHas('posts', fn ($posts) => $posts->total() === 1)
            ->set('search', 'no-matching-story')
            ->assertSee('No posts found')
            ->set('search', '')
            ->assertViewHas('posts', fn ($posts) => $posts->total() === 3);
    }
}
