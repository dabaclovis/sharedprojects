<?php

namespace Tests\Feature;

use App\Livewire\Pages\Notes;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class QuotesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_view_and_share_quotes(): void
    {
        $this->get(route('pages.quotes'))->assertOk()->assertSee('Be the first to share a quote');

        Livewire::test(Notes::class)
            ->set('content', '  Keep going.  ')->set('author', 'A writer')
            ->set('category', 'Inspiration')->call('save')->assertHasNoErrors()
            ->assertSee('Your quote has been shared!')->assertSee('Keep going.')
            ->assertSee('fa-quote-left')->assertSee('fa-quote-right')->assertSet('content', '');

        $quote = Quote::sole();
        $this->assertSame('Keep going.', $quote->content);
        $this->assertSame('A writer', $quote->author);
        $this->assertSame('fa-quote-left', $quote->licon);
        $this->assertSame('fa-quote-right', $quote->ricon);
        $this->assertNotNull($quote->ipaddr);
        $this->assertArrayNotHasKey('ipaddr', $quote->toArray());
        $this->assertGuest();
    }

    public function test_content_is_required_and_fields_have_limits(): void
    {
        Livewire::test(Notes::class)->set('content', '   ')->call('save')
            ->assertHasErrors(['content' => 'required'])
            ->set('content', str_repeat('x', 5001))->set('author', str_repeat('a', 256))
            ->call('save')->assertHasErrors(['content' => 'max', 'author' => 'max']);
        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_quotes_are_escaped_and_searchable_with_pagination(): void
    {
        for ($i = 0; $i < 10; $i++) {
            Quote::create(['content' => 'Thought '.$i]);
        }
        Quote::create(['content' => '<script>alert(1)</script>', 'author' => 'Distinct author']);

        Livewire::test(Notes::class)
            ->assertSeeHtml('&lt;script&gt;alert(1)&lt;/script&gt;')
            ->assertDontSeeHtml('<script>alert(1)</script>')
            ->assertViewHas('quotes', fn ($quotes) => $quotes->total() === 11 && $quotes->count() === 3)
            ->call('nextPage')->assertSet('paginators.page', 2)
            ->set('search', 'Distinct')->assertSet('paginators.page', 1)
            ->assertViewHas('quotes', fn ($quotes) => $quotes->total() === 1)
            ->set('search', 'not present')->assertSee('No matching quotes');
    }

    public function test_public_submissions_are_rate_limited(): void
    {
        $key = 'quotes:create:'.hash('sha256', '127.0.0.1');
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($key, 60);
        }

        Livewire::test(Notes::class)->set('content', 'Too many submissions')->call('save')
            ->assertHasErrors('content');
        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_guests_can_select_save_and_display_each_icon(): void
    {
        Livewire::test(Notes::class)->set('content', 'Grow with kindness.')
            ->set('licon', 'fa-leaf')->set('ricon', 'fa-heart')->call('save')
            ->assertHasNoErrors()->assertSet('licon', 'fa-quote-left')->assertSet('ricon', 'fa-quote-right');

        $this->assertDatabaseHas('quotes', ['licon' => 'fa-leaf', 'ricon' => 'fa-heart']);
        $this->get(route('pages.quotes'))->assertOk()
            ->assertSee('fa-leaf quote-decoration', false)
            ->assertSee('fa-heart quote-decoration quote-decoration-right', false);
    }

    public function test_unlisted_icons_are_rejected(): void
    {
        Livewire::test(Notes::class)->set('content', 'A quote')
            ->set('licon', 'invalid-icon')->set('ricon', '<script>')
            ->call('save')->assertHasErrors(['licon', 'ricon']);
        $this->assertDatabaseCount('quotes', 0);
    }
}
