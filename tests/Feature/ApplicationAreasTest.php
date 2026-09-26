<?php

namespace Tests\Feature;

use App\Livewire\Admins\ContentManager;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApplicationAreasTest extends TestCase
{
    use RefreshDatabase;

    private function article(User $owner): Post
    {
        $post = new Post(['title' => 'Community submission', 'slug' => 'community-submission', 'content' => 'Review this article.']);
        $post->author()->associate($owner);
        $post->postsable()->associate($owner);
        $post->save();

        return $post;
    }

    public function test_navigation_follows_the_area_even_for_admins(): void
    {
        $this->get(route('pages.business'))->assertOk()->assertSee('Main navigation');
        $this->get(route('users.products'))->assertRedirect(route('auth.login'));
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('pages.index'))->assertOk()->assertSee('Main navigation')->assertSee('My workspace');
        $this->get(route('users.index'))->assertOk()->assertSee('aria-label="My workspace"', false);
        $this->get(route('admins.index'))->assertOk()->assertSee('aria-label="Administration"', false);
        $this->get('/services/calendar')->assertRedirect(route('users.calendar'));
        $this->get('/admin/profile')->assertRedirect(route('users.profile'));
        $this->get('/admin/articles')->assertRedirect(route('admins.articles'));
        $this->get('/services/web-crawler')->assertRedirect(route('pages.web-crawler'));
    }

    public function test_admin_moderation_and_personal_workspace_have_different_scopes(): void
    {
        $post = $this->article(User::factory()->create());
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('users.articles'))->assertOk()->assertDontSee($post->title);
        $this->get(route('admins.articles'))->assertOk()->assertSee($post->title);
        $component = Livewire::test(ContentManager::class, ['kind' => 'articles'])
            ->call('review', $post->id)->set('remark', 'Please add sources.')->call('sendRemark')->assertHasNoErrors()
            ->call('publish')->assertHasNoErrors();
        $this->assertDatabaseHas('remarks', ['post_id' => $post->id, 'admin_id' => $admin->id, 'message' => 'Please add sources.']);
        $this->assertSame('published', $post->fresh()->status);
        $component->call('review', $post->id)->call('trash')->assertHasNoErrors();
        $this->assertSoftDeleted($post);
        $component->set('status', 'trash')->assertSee($post->title)->call('review', $post->id)->call('restore')->assertHasNoErrors();
        $this->assertSame('draft', $post->fresh()->status);
        $this->assertNull($post->fresh()->published_at);
    }

    public function test_stale_reviews_and_revoked_admin_access_cannot_mutate_content(): void
    {
        $post = $this->article(User::factory()->create());
        $admin = User::factory()->create(['role' => 'admin']);
        $component = Livewire::actingAs($admin)->test(ContentManager::class)->call('review', $post->id);
        $post->update(['content' => 'New version from the author']);
        $component->call('publish')->assertHasErrors('review');
        $this->assertSame('draft', $post->fresh()->status);
        $admin->update(['role' => 'user']);
        $component->call('trash')->assertForbidden();
        $this->assertNotSoftDeleted($post);
    }

    public function test_product_and_event_management_apply_safe_restore_states(): void
    {
        $owner = User::factory()->create();
        $product = $owner->affiliateProducts()->create(['title' => 'Owner product', 'description' => 'Product details', 'merchant' => 'Example', 'affiliate_url' => 'https://example.com/item']);
        $event = $owner->events()->create(['title' => 'Owner event', 'starts_at' => now(), 'ends_at' => now()->addHour()]);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admins.products'))->assertOk()->assertSee('Manage products')->assertSee($product->title);
        $this->get(route('admins.calendar'))->assertOk()->assertSee('Manage events')->assertSee($event->title);
        Livewire::test(ContentManager::class, ['kind' => 'products'])->call('review', $product->id)->call('publish')->assertHasNoErrors();
        $this->assertSame('published', $product->fresh()->status);
        $component = Livewire::test(ContentManager::class, ['kind' => 'events'])->call('review', $event->id)->call('archive')->assertHasNoErrors();
        $this->assertSame('cancelled', $event->fresh()->status);
        $component->call('review', $event->id)->call('trash')->call('review', $event->id)->call('restore')->assertHasNoErrors();
        $this->assertSame('cancelled', $event->fresh()->status);
    }
}
