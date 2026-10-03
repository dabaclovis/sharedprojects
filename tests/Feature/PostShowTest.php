<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_active_users_can_comment_and_reply_to_a_published_post(): void
    {
        $post = $this->createPost();
        $firstUser = User::factory()->create(['username' => 'reader_one']);
        $secondUser = User::factory()->create(['username' => 'reader_two']);
        $component = Livewire::actingAs($firstUser)->test(\App\Livewire\Pages\PostShow::class, ['slug' => $post->slug])
            ->set('newComment', 'A useful comment.')
            ->call('submitComment')->assertHasNoErrors()->assertSee('A useful comment.');
        $comment = PostComment::sole();
        $this->assertSame($firstUser->id, $comment->user_id);
        $this->assertNull($comment->parent_id);

        Livewire::actingAs($secondUser)->test(\App\Livewire\Pages\PostShow::class, ['slug' => $post->slug])
            ->call('openReply', $comment->id)
            ->set('replyBody', 'A thoughtful reply.')
            ->call('submitReply')->assertHasNoErrors()->assertSee('A thoughtful reply.');
        $reply = PostComment::whereNotNull('parent_id')->sole();
        $this->assertSame($comment->id, $reply->parent_id);
        $this->assertSame($secondUser->id, $reply->user_id);
        $this->assertSame(2, $post->comments()->count());
    }

    public function test_guests_and_inactive_users_cannot_post_comments(): void
    {
        $post = $this->createPost();
        $this->get(route('pages.postshow', $post->slug))->assertOk()->assertSee('Sign in to join the discussion.');
        Livewire::test(\App\Livewire\Pages\PostShow::class, ['slug' => $post->slug])
            ->set('newComment', 'Guest comment')->call('submitComment')->assertForbidden();

        $inactiveUser = User::factory()->create(['status' => 'inactive']);
        Livewire::actingAs($inactiveUser)->test(\App\Livewire\Pages\PostShow::class, ['slug' => $post->slug])
            ->set('newComment', 'Inactive comment')->call('submitComment')->assertForbidden();
        $this->assertDatabaseCount('post_comments', 0);
    }
}
