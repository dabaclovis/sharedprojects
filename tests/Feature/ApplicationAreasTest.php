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

    private function assertNoPublicPageLinks(string $html): void
    {
        $document = new \DOMDocument;
        $previousErrorMode = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorMode);

        foreach ($document->getElementsByTagName('a') as $link) {
            $href = $link->getAttribute('href');
            $path = parse_url($href, PHP_URL_PATH) ?: '';
            $this->assertNotSame(route('pages.index'), $href, 'Authenticated links must not target the public home route.');
            $this->assertFalse(str_starts_with($path, '/pages/'), 'Authenticated links must not target the pages route namespace: ' . $href);
        }
    }

    public function test_navigation_follows_the_area_even_for_admins(): void
    {
        $this->get(route('pages.business'))->assertOk()->assertSee('Main navigation');
        $this->get(route('users.products'))->assertRedirect(route('auth.login'));
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('pages.index'))->assertOk()
            ->assertSee('aria-label="Administration"', false)->assertDontSee('Main navigation')
            ->assertDontSee('href="' . route('pages.business') . '"', false)
            ->assertDontSee('href="' . route('pages.seo-audit') . '"', false);
        $this->get(route('users.index'))->assertOk()->assertSee('aria-label="My workspace"', false);
        $this->get(route('admins.index'))->assertOk()->assertSee('aria-label="Administration"', false);
        $this->actingAs(User::factory()->create())->get(route('users.index'))->assertOk()
            ->assertSee('>More</button>', false)
            ->assertSee('href="' . route('users.articles') . '"', false)
            ->assertSee('href="' . route('users.withdrawals') . '"', false)
            ->assertSee('href="' . route('users.products') . '"', false)
            ->assertSee('href="' . route('users.calendar') . '"', false)
            ->assertDontSee('>Overview</a>', false);
        $this->actingAs($admin)->get(route('admins.index'))->assertOk()
            ->assertDontSee('>Overview</a>', false);
        $this->get('/services/calendar')->assertRedirect(route('users.calendar'));
        $this->get('/admin/profile')->assertRedirect(route('users.profile'));
        $this->get('/admin/articles')->assertRedirect(route('admins.articles'));
        $this->get('/services/web-crawler')->assertRedirect(route('pages.web-crawler'));
    }

    public function test_authenticated_pages_and_workspaces_do_not_render_public_page_links(): void
    {
        $member = User::factory()->create();
        $post = $this->article($member);
        $post->forceFill(['status' => 'published', 'published_at' => now()])->save();
        $member->affiliateProducts()->create([
            'title' => 'Member product',
            'description' => 'Product details',
            'merchant' => 'Example',
            'affiliate_url' => 'https://example.com/item',
            'status' => 'published',
        ]);

        $publicPages = [
            'pages.index',
            'pages.about',
            'pages.business',
            'pages.articles',
            'pages.products',
            'pages.contact',
            'pages.policy',
            'pages.seo-audit',
            'pages.web-crawler',
            'pages.timezone-converter',
            'pages.word-counter',
        ];
        foreach ([$member, User::factory()->create(['role' => 'admin'])] as $account) {
            $this->actingAs($account);
            foreach ($publicPages as $routeName) {
                $response = $this->get(route($routeName))->assertOk();
                $this->assertNoPublicPageLinks($response->getContent());
            }
        }

        $this->actingAs($member);
        $this->assertNoPublicPageLinks($this->get(route('users.index'))->assertOk()->getContent());
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        foreach (['admins.index', 'admins.website-audits', 'admins.sponsorships'] as $routeName) {
            $response = $this->get(route($routeName))->assertOk();
            $this->assertNoPublicPageLinks($response->getContent());
        }
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

    public function test_admin_article_manager_capitalizes_titles_and_displays_owner_username(): void
    {
        $owner = User::factory()->create(['name' => 'judith danji', 'username' => 'judith_danji']);
        $post = $this->article($owner);
        $post->update(['title' => 'my educational journey in America']);
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)->test(ContentManager::class, ['kind' => 'articles'])
            ->assertSee('My educational journey in America')
            ->assertSee('judith_danji')->assertDontSee('judith danji');
    }

    public function test_admin_review_limits_article_content_to_350_characters(): void
    {
        $owner = User::factory()->create();
        $post = $this->article($owner);
        $content = str_repeat('A', 400);
        $post->update(['content' => $content]);
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)->test(ContentManager::class, ['kind' => 'articles'])
            ->call('review', $post->id)
            ->assertSee(\Illuminate\Support\Str::limit($content, 350))
            ->assertDontSee($content);

        $this->assertSame($content, $post->fresh()->content);
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
