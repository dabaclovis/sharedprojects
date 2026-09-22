<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostShowTest extends TestCase
{
    use RefreshDatabase;

    private function createPost(): Post
    {
        $author = User::factory()->create();
        $post = new Post(['title' => 'a new story', 'slug' => 'a-new-story', 'content' => '<script>alert(1)</script>']);
        $post->author()->associate($author);
        $post->postsable()->associate($author);
        $post->status = 'published';
        $post->published_at = now()->subDay();
        $post->save();

        return $post;
    }

    public function test_public_list_links_to_capitalized_article_and_reader_escapes_content(): void
    {
        $post = $this->createPost();
        $this->get(route('pages.articles'))->assertOk()->assertSee('A new story')->assertSee(route('pages.postshow', $post->slug));
        $this->get(route('pages.postshow', $post->slug))->assertOk()
            ->assertSee('A new story')->assertDontSee($post->author->name)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_unpublished_future_deleted_and_missing_articles_return_404(): void
    {
        $post = $this->createPost();
        foreach (['draft', 'archived'] as $status) {
            $post->status = $status;
            $post->save();
            $this->get(route('pages.postshow', $post->slug))->assertNotFound();
        }
        $post->status = 'published';
        $post->published_at = now()->addDay();
        $post->save();
        $this->get(route('pages.postshow', $post->slug))->assertNotFound();
        $post->published_at = null;
        $post->save();
        $this->get(route('pages.postshow', $post->slug))->assertNotFound();
        $post->published_at = now()->subDay();
        $post->save();
        $post->delete();
        $this->get(route('pages.postshow', $post->slug))->assertNotFound();
        $this->get(route('pages.postshow', 'missing'))->assertNotFound();
    }
}
