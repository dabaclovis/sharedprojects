<?php

namespace Tests\Feature;

use App\Livewire\Admins\Dashboard;
use App\Livewire\Users\Articles;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
            Livewire::actingAs($admin)->test(Dashboard::class)
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
            } catch (ModelNotFoundException $exception) {
                $this->assertSame(Post::class, $exception->getModel());
            }
        }
        $this->assertNotSoftDeleted($post);
    }

    public function test_non_admins_cannot_approve_articles(): void
    {
        $post = $this->article(User::factory()->create());
        $admin = User::factory()->create(['role' => 'admin']);
        $component = Livewire::actingAs($admin)->test(Dashboard::class);
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
        $component = Livewire::actingAs($admin)->test(Dashboard::class)
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
        $component = Livewire::actingAs($admin)->test(Dashboard::class)
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

    public function test_trash_can_be_filtered_and_restored_only_by_its_author(): void
    {
        $author = User::factory()->create();
        $post = $this->article($author);
        $post->forceFill(['status' => 'published', 'published_at' => now()])->save();
        $component = Livewire::actingAs($author)->test(Articles::class)
            ->call('delete', $post->id)->assertDontSee('Private draft')
            ->set('filter', 'trash')->assertSee('Private draft');
        $this->assertSoftDeleted($post);
        $component->call('restore', $post->id)->assertDontSee('Private draft');
        $this->assertNotSoftDeleted($post);
        $this->assertSame('draft', $post->fresh()->status);
        $this->assertNull($post->fresh()->published_at);
        $post->delete();
        try {
            Livewire::actingAs(User::factory()->create())->test(Articles::class)->call('restore', $post->id);
            $this->fail('Another account must not restore the article.');
        } catch (ModelNotFoundException $exception) {
            $this->assertSame(Post::class, $exception->getModel());
        }
        $this->assertSoftDeleted($post);
    }

    public function test_stale_editor_preserves_unsaved_work_and_does_not_overwrite_changes(): void
    {
        $author = User::factory()->create();
        $post = $this->article($author);
        $component = Livewire::actingAs($author)->test(Articles::class)->call('edit', $post->id);
        $post->update(['content' => 'Changed in another tab']);
        $component->set('content', 'My unsaved edits')->call('save')
            ->assertHasErrors('conflict')->assertSet('showEditor', true)->assertSet('content', 'My unsaved edits');
        $this->assertSame('Changed in another tab', $post->fresh()->content);
        $post->delete();
        $component->call('save')->assertHasErrors('conflict');
        $this->assertSoftDeleted($post);
    }

    public function test_admin_cannot_publish_a_revision_that_changed_during_review(): void
    {
        $post = $this->article(User::factory()->create());
        $admin = User::factory()->create(['role' => 'admin']);
        $component = Livewire::actingAs($admin)->test(Dashboard::class)
            ->call('reviewArticle', $post->id);
        $post->update(['content' => 'Unreviewed revision']);
        $component->call('publishReviewedArticle')->assertHasErrors('reviewConflict');
        $this->assertSame('draft', $post->fresh()->status);
        $component->call('reviewArticle', $post->id)->call('publishReviewedArticle')->assertHasNoErrors();
        $this->assertSame('published', $post->fresh()->status);
        $component->call('reviewArticle', $post->id);
        $post->delete();
        $component->call('archiveReviewedArticle')->assertSee('This article is no longer available.');
        $this->assertSoftDeleted($post);
    }

    public function test_archive_and_status_filters_are_scoped_to_author(): void
    {
        $author = User::factory()->create();
        $post = $this->article($author);
        $other = $this->article(User::factory()->create());
        $other->update(['title' => 'Another account story']);
        Livewire::actingAs($author)->test(Articles::class)
            ->call('archive', $post->id)->set('filter', 'draft')->assertDontSee('Private draft')
            ->set('filter', 'archived')->assertSee('Private draft')->assertDontSee('Another account story')
            ->set('search', 'Missing title')->assertDontSee('Private draft')
            ->call('clearFilters')->assertSee('Private draft')->assertSet('filter', '');
        $this->assertSame('archived', $post->fresh()->status);
        $this->assertSame('draft', $other->fresh()->status);
    }
}
