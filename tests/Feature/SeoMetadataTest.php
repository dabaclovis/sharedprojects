<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_components_render_unique_search_metadata(): void
    {
        foreach (['home', 'pages.articles', 'pages.products', 'pages.quotes', 'pages.word-counter'] as $route) {
            $metadata = config('seo.routes')[$route];

            $this->get(route($route))->assertOk()
                ->assertSee('<title>'.$metadata['title'].'</title>', false)
                ->assertSee('name="description" content="'.$metadata['description'].'"', false)
                ->assertSee('name="keywords" content="'.$metadata['keywords'].'"', false)
                ->assertSee('name="robots" content="index, follow"', false)
                ->assertSee('rel="canonical"', false);
        }
    }

    public function test_article_reader_uses_article_specific_metadata(): void
    {
        $author = User::factory()->create();
        $post = new Post([
            'title' => 'A focused article title',
            'slug' => 'focused-article',
            'excerpt' => 'A concise article-specific search description.',
            'content' => 'Article body',
            'category' => 'technology',
        ]);
        $post->author()->associate($author);
        $post->postsable()->associate($author);
        $post->status = 'published';
        $post->published_at = now();
        $post->save();

        $this->get(route('pages.postshow', $post->slug))->assertOk()
            ->assertSee('<title>A focused article title</title>', false)
            ->assertSee('content="A concise article-specific search description."', false)
            ->assertSee('content="technology, community article, stories, ideas"', false);
    }

    public function test_private_components_are_not_indexed(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)->get(route('users.index'))->assertOk()
            ->assertSee('<title>Your Content Dashboard | CD</title>', false)
            ->assertSee('name="robots" content="noindex, nofollow"', false)
            ->assertDontSee('rel="canonical"', false);
    }
}
