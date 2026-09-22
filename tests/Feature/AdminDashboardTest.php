<?php

namespace Tests\Feature;

use App\Livewire\Admins\Dashboard;
use App\Models\Post;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_edit_a_quote_in_a_modal(): void
    {
        $quote = Quote::create(['content' => 'Original words', 'title' => 'Original']);
        $admin = User::factory()->create(['role' => 'admin']);
        $component = Livewire::actingAs($admin)->test(Dashboard::class)->set('section', 'quotes')
            ->call('editQuote', $quote->id)->assertSet('editingQuoteId', $quote->id)
            ->assertSet('quoteFields.content', 'Original words')
            ->set('quoteFields.content', '   ')->call('saveQuote')->assertHasErrors('quoteFields.content')
            ->set('quoteFields.content', 'Revised words')->set('quoteFields.author', 'Test author')
            ->set('quoteFields.category', 'Wisdom')->set('quoteFields.licon', 'fa-leaf')
            ->call('saveQuote')->assertHasNoErrors()->assertSet('editingQuoteId', null)->assertSee('Quote updated.');
        $this->assertSame('Revised words', $quote->fresh()->content);
        $this->assertSame('Test author', $quote->fresh()->author);
        $component->call('editQuote', $quote->id)->set('quoteFields.content', 'Discarded')->call('closeQuoteEditor');
        $this->assertSame('Revised words', $quote->fresh()->content);
        $component->call('editQuote', $quote->id);
        $admin->update(['role' => 'user']);
        $component->call('saveQuote')->assertForbidden();
        $this->assertSame('Revised words', $quote->fresh()->content);
    }

    public function test_dashboard_requires_an_active_admin(): void
    {
        $this->get(route('admins.index'))->assertRedirect(route('auth.login'));
        $this->actingAs(User::factory()->create())->get(route('admins.index'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'inactive']))
            ->get(route('admins.index'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admins.index'))->assertOk()->assertSee('Admin dashboard')->assertSee('Quote library');
    }

    public function test_access_is_rechecked_on_component_updates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $component = Livewire::actingAs($admin)->test(Dashboard::class);
        $admin->update(['role' => 'user']);
        $component->call('clearFilters')->assertForbidden();
    }

    public function test_dashboard_internal_links_use_protected_admin_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get(route('admins.index'))->assertOk();
        preg_match_all('/(?:href|action)="([^"]+)"/', $response->getContent(), $matches);
        foreach ($matches[1] as $url) {
            if (str_starts_with($url, url('/').'/') && ! str_starts_with($url, asset('css').'/') && ! str_starts_with($url, asset('images').'/')) {
                $this->assertStringStartsWith(url('/admin').'/', $url);
            }
        }

        $routes = ['admins.users', 'admins.calendar', 'admins.articles', 'admins.products', 'admins.profile', 'admins.setting',
            'admins.pages.articles', 'admins.pages.products', 'admins.pages.quotes', 'admins.about', 'admins.contact', 'admins.policy'];
        foreach ($routes as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->actingAs(User::factory()->create());
        foreach ($routes as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'inactive']))
            ->get(route('admins.calendar'))->assertForbidden();
    }

    public function test_admin_can_browse_all_scheduled_events_across_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['name' => 'Calendar owner']);
        for ($i = 0; $i < 9; $i++) {
            ($i === 0 ? $admin : $member)->events()->create([
                'title' => 'Scheduled appointment '.$i,
                'starts_at' => now('UTC')->startOfDay()->subDay()->addDays($i),
                'ends_at' => now('UTC')->startOfDay()->subDay()->addDays($i)->addHour(),
            ]);
        }
        $member->events()->create([
            'title' => 'Cancelled appointment', 'status' => 'cancelled',
            'starts_at' => now('UTC'), 'ends_at' => now('UTC')->addHour(),
        ]);
        $member->events()->create([
            'title' => 'Deleted appointment',
            'starts_at' => now('UTC'), 'ends_at' => now('UTC')->addHour(),
        ])->delete();

        Livewire::actingAs($admin)->test(Dashboard::class)
            ->assertSee('All scheduled events')->assertSee('Calendar owner')
            ->assertSee('Scheduled appointment 0')->assertSee('Scheduled appointment 1')
            ->assertDontSee('Cancelled appointment')->assertDontSee('Deleted appointment')
            ->assertDontSee('View website')
            ->assertViewHas('scheduledEvents', fn ($events) => $events->total() === 9 && $events->count() === 8)
            ->call('nextPage', 'eventsPage')->assertSee('Scheduled appointment 8')
            ->assertViewHas('records', fn ($records) => $records->currentPage() === 1);
    }

    public function test_quotes_can_be_searched_and_paginated_without_a_status_column(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        for ($i = 1; $i <= 9; $i++) {
            $quote = new Quote;
            $quote->title = 'Quote '.$i;
            $quote->content = $i === 9 ? 'A distinctive thought' : 'Words of wisdom';
            $quote->author = $i === 9 ? 'Unique author' : null;
            $quote->save();
        }

        Livewire::actingAs($admin)->test(Dashboard::class)
            ->assertViewHas('stats', fn ($stats) => $stats['quotes'] === 9 && $stats['newQuotes'] === 9)
            ->set('status', 'draft')->set('section', 'quotes')->assertSet('status', '')
            ->assertViewHas('records', fn ($records) => $records->total() === 9 && $records->count() === 8)
            ->call('nextPage')->assertSet('paginators.page', 2)
            ->set('search', 'distinctive')->assertSet('paginators.page', 1)->assertSee('A distinctive thought')
            ->assertViewHas('records', fn ($records) => $records->total() === 1)
            ->set('search', 'Unique author')->assertSee('A distinctive thought')
            ->set('status', 'published')->assertSee('A distinctive thought')
            ->set('search', 'missing')->assertSee('No quotes match these filters.')
            ->call('clearFilters')->assertSet('search', '')->assertSet('status', '')
            ->set('section', 'invalid')->assertViewHas('activeSection', 'articles');
    }

    public function test_content_overview_includes_other_authors_and_excludes_deleted_posts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create();
        foreach (['Community draft', 'Removed draft'] as $title) {
            $post = new Post(['title' => $title, 'slug' => fake()->uuid(), 'content' => 'Content']);
            $post->author()->associate($author);
            $post->postsable()->associate($author);
            $post->save();
            if ($title === 'Removed draft') {
                $post->delete();
            }
        }

        Livewire::actingAs($admin)->test(Dashboard::class)
            ->assertSee('Community draft')->assertDontSee('Removed draft')
            ->assertViewHas('stats', fn ($stats) => $stats['articles'] === 1 && $stats['draftArticles'] === 1)
            ->set('status', 'published')->assertSee('No articles match these filters.')
            ->set('status', 'draft')->set('search', 'Community')->assertSee('Community draft')
            ->set('section', 'products')->assertSet('search', '')->assertSet('status', '')
            ->assertSee('No products match these filters.');
    }
}
