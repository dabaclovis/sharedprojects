<?php

namespace Tests\Feature;

use App\Livewire\Users\Articles;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArticlesTest extends TestCase
{
    use RefreshDatabase;

    private function article(User $user): Post
    {
        $post = new Post(['title' => 'Private draft', 'slug' => fake()->uuid(), 'content' => 'Draft text']);
        $post->author()->associate($user);
        $post->postsable()->associate($user);
        $post->save();

        return $post;
    }

    public function test_article_excerpt_is_generated_from_content_when_saved(): void
    {
        $user = User::factory()->create();
        $component = Livewire::actingAs($user)->test(Articles::class)
            ->call('create')->assertDontSee('article-excerpt')
            ->set('title', 'A story')->set('content', "<p>First paragraph.</p>\n\nSecond paragraph.")
            ->call('save')->assertHasNoErrors();

        $post = $user->posts()->firstOrFail();
        $this->assertSame('First paragraph. Second paragraph.', $post->excerpt);

        $component->call('edit', $post->id)
            ->set('content', '<p>Updated story.</p>')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('Updated story.', $post->fresh()->excerpt);
    }

    public function test_articles_require_admin_approval_to_publish(): void
    {
        foreach (['user', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $component = Livewire::actingAs($user)->test(Articles::class)
                ->call('create')->assertSet('showEditor', true)
                ->call('save')->assertHasErrors(['title', 'content'])
                ->set('title', 'Unpublished story title')->set('content', '<script>alert(1)</script>')
                ->call('save')->assertHasNoErrors()->assertSet('showEditor', false);
            $post = $user->posts()->firstOrFail();
            $this->assertSame('draft', $post->status);
            $this->assertTrue($post->postsable->is($user));
            $this->get(route('pages.articles'))->assertDontSee('Unpublished story title');
            $slug = $post->slug;
            $component->call('edit', $post->id)->assertSet('content', '<script>alert(1)</script>')
                ->set('title', 'Published article')->set('status', 'published')->call('save')->assertHasNoErrors();
            $this->assertSame($slug, $post->fresh()->slug);
            $this->assertSame('draft', $post->fresh()->status);
            $this->assertNull($post->fresh()->published_at);
            $this->get(route('pages.postshow', $slug))->assertNotFound();
            $admin = User::factory()->create(['role' => 'admin']);
            Livewire::actingAs($admin)->test(\App\Livewire\Admins\Dashboard::class)
                ->call('reviewArticle', $post->id)->assertSeeHtml('&lt;script&gt;alert(1)&lt;/script&gt;')
                ->call('approveArticle', $post->id)->assertSee('Article approved and published.');
            $this->assertNotNull($post->fresh()->published_at);
            $this->get(route('pages.articles'))->assertSee('Published article')->assertDontSee('<script>alert(1)</script>', false);
            $this->actingAs($user);
            $component->call('edit', $post->id)->set('content', 'Revised content')->call('save')->assertHasNoErrors();
            $this->assertSame('draft', $post->fresh()->status);
            $this->assertNull($post->fresh()->published_at);
            $this->get(route('pages.postshow', $slug))->assertNotFound();
            $component->call('delete', $post->id);
            $this->assertSoftDeleted($post);
        }
    }

    public function test_other_accounts_cannot_read_edit_or_delete_an_article(): void
    {
        $post = $this->article(User::factory()->create());
        $user = User::factory()->create();
        foreach (['edit', 'delete'] as $action) {
            try {
                Livewire::actingAs($user)->test(Articles::class)->assertDontSee('Private draft')->call($action, $post->id);
                $this->fail('Another account must not access the article.');
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
                $this->assertSame(Post::class, $exception->getModel());
            }
        }
        $this->assertNotSoftDeleted($post);
    }

    public function test_non_admins_cannot_approve_articles(): void
    {
        $post = $this->article(User::factory()->create());
        $admin = User::factory()->create(['role' => 'admin']);
        $component = Livewire::actingAs($admin)->test(\App\Livewire\Admins\Dashboard::class);
        $admin->update(['role' => 'user']);
        $component->call('approveArticle', $post->id)->assertForbidden();
        $this->assertSame('draft', $post->fresh()->status);
        $this->assertNull($post->fresh()->published_at);
    }

    public function test_admin_remarks_are_delivered_privately_to_the_author(): void
    {
        $author = User::factory()->create();
        $post = $this->article($author);
        $admin = User::factory()->create(['role' => 'admin']);
        $component = Livewire::actingAs($admin)->test(\App\Livewire\Admins\Dashboard::class)
            ->call('reviewArticle', $post->id)
            ->set('remarkMessage', '   ')->call('sendRemark')->assertHasErrors('remarkMessage')
            ->set('remarkMessage', 'Please remove the insulting language. <script>alert(1)</script>')
            ->call('sendRemark')->assertHasNoErrors()->assertSet('remarkMessage', '')
            ->assertSeeHtml('&lt;script&gt;alert(1)&lt;/script&gt;');
        $this->assertDatabaseHas('remarks', ['post_id' => $post->id, 'admin_id' => $admin->id]);
        $component->call('publishReviewedArticle');
        $this->get(route('pages.postshow', $post->slug))->assertDontSee('Please remove the insulting language.');
        Livewire::actingAs($author)->test(Articles::class)->assertSee('Please remove the insulting language.')
            ->assertDontSeeHtml('<script>alert(1)</script>');
        Livewire::actingAs(User::factory()->create())->test(Articles::class)
            ->assertDontSee('Please remove the insulting language.');
        $this->actingAs($admin);
        $component->call('reviewArticle', $post->id);
        $admin->update(['role' => 'user']);
        $component->set('remarkMessage', 'Unauthorized message')->assertForbidden();
        $this->assertDatabaseCount('remarks', 1);
    }

    public function test_admin_can_review_publish_archive_and_republish_another_users_post(): void
    {
        $post = $this->article(User::factory()->create());
        $admin = User::factory()->create(['role' => 'admin']);
        $component = Livewire::actingAs($admin)->test(\App\Livewire\Admins\Dashboard::class)
            ->assertSee('Private draft')->call('reviewArticle', $post->id)
            ->assertSee('Draft text')->assertSee('Archive')->assertSee('Publish')
            ->call('publishReviewedArticle')->assertSet('reviewPostId', null);
        $this->get(route('pages.postshow', $post->slug))->assertOk();
        $component->call('reviewArticle', $post->id)->call('archiveReviewedArticle')
            ->assertSet('reviewPostId', null);
        $this->assertSame('archived', $post->fresh()->status);
        $this->get(route('pages.postshow', $post->slug))->assertNotFound();
        $component->call('reviewArticle', $post->id)->call('publishReviewedArticle');
        $this->assertSame('published', $post->fresh()->status);
        $component->call('reviewArticle', $post->id);
        $admin->update(['role' => 'user']);
        $component->call('archiveReviewedArticle')->assertForbidden();
        $this->assertSame('published', $post->fresh()->status);
    }

    public function test_guests_and_inactive_accounts_are_blocked(): void
    {
        $this->get(route('users.articles'))->assertRedirect(route('auth.login'));
        $user = User::factory()->create();
        $component = Livewire::actingAs($user)->test(Articles::class);
        $user->status = 'inactive';
        $user->save();
        $component->call('create')->assertForbidden();
    }

    public function test_drafts_archives_and_future_posts_are_not_public(): void
    {
        $user = User::factory()->create();
        foreach (['draft', 'archived', 'published'] as $status) {
            $post = $this->article($user);
            $post->status = $status;
            $post->published_at = now()->addDay();
            $post->save();
        }
        $this->get(route('pages.articles'))->assertOk()->assertDontSee('Private draft');
    }
}
