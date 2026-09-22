<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    private function createPost(User $author, string $slug): Post
    {
        $post = new Post(['title' => 'Example post', 'slug' => $slug, 'content' => 'Post content']);
        $post->author()->associate($author);
        $post->postsable()->associate($author);
        $post->save();

        return $post;
    }

    public function test_posts_default_to_drafts_and_resolve_author_and_owner(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost($author, 'example');

        $this->assertSame('draft', $post->fresh()->status);
        $this->assertNull($post->published_at);
        $this->assertTrue($post->author->is($author));
        $this->assertTrue($post->postsable->is($author));
        $this->assertTrue($author->posts->first()->is($post));
    }

    public function test_public_scope_excludes_drafts_archives_future_and_deleted_posts(): void
    {
        $author = User::factory()->create();
        $this->createPost($author, 'draft');
        $visible = $this->createPost($author, 'visible');
        $visible->status = 'published';
        $visible->published_at = now()->subDay();
        $visible->save();

        foreach (['archived', 'future', 'undated', 'deleted'] as $slug) {
            $post = $this->createPost($author, $slug);
            $post->status = $slug === 'archived' ? 'archived' : 'published';
            $post->published_at = match ($slug) {
                'future' => now()->addDay(),
                'undated' => null,
                default => now()->subDay(),
            };
            $post->save();
            if ($slug === 'deleted') {
                $post->delete();
                $this->assertSoftDeleted($post);
            }
        }

        $this->assertSame([$visible->id], Post::published()->pluck('id')->all());
        $deleted = Post::onlyTrashed()->firstOrFail();
        $deleted->restore();
        $this->assertSame(2, Post::published()->count());
    }

    public function test_slugs_must_be_unique(): void
    {
        $author = User::factory()->create();
        $this->createPost($author, 'same-slug');
        $this->expectException(QueryException::class);
        $this->createPost($author, 'same-slug');
    }

    public function test_deleting_an_author_preserves_the_post(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost($author, 'retained');
        $author->delete();

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'author_id' => null]);
    }
}
