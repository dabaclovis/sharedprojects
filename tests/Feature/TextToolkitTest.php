<?php

namespace Tests\Feature;

use App\Livewire\Services\TextToolkit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TextToolkitTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_find_and_open_toolkit(): void
    {
        $this->get(route('services.text-toolkit'))->assertOk()->assertSee('No account needed');
        $this->get('/')->assertSee(route('services.text-toolkit'));
        $this->assertGuest();
    }

    public function test_unicode_case_conversion_undo_and_download(): void
    {
        Livewire::test(TextToolkit::class)->set('text', 'Café world')
            ->call('transform', 'upper')->assertSet('text', 'CAFÉ WORLD')
            ->call('undo')->assertSet('text', 'Café world')->assertSet('previousText', null)
            ->call('transform', 'lower')->assertSet('text', 'café world')
            ->call('transform', 'title')->assertSet('text', 'Café World')
            ->call('download')->assertFileDownloaded('edited-text.txt')
            ->call('transform', 'clear')->assertSet('text', '')
            ->call('undo')->assertSet('text', 'Café World');
    }

    public function test_cleanup_preserves_order_and_exact_line_comparison(): void
    {
        Livewire::test(TextToolkit::class)->set('text', " Apple  pie \r\n\r\nApple pie\r\napple pie\r\nPear")
            ->call('transform', 'spaces')->assertSet('text', "Apple pie\n\nApple pie\napple pie\nPear")
            ->call('transform', 'blank')->assertSet('text', "Apple pie\nApple pie\napple pie\nPear")
            ->call('transform', 'duplicates')->assertSet('text', "Apple pie\napple pie\nPear");
    }

    public function test_counts_limits_and_invalid_actions(): void
    {
        Livewire::test(TextToolkit::class)->set('text', "Café isn't time-consuming.")
            ->assertViewHas('counts', fn ($counts) => $counts['Words'] === 3 && $counts['Reading minutes'] === 1)
            ->call('transform', 'invalid')->assertHasErrors('text')
            ->set('text', '')->assertViewHas('counts', fn ($counts) => array_sum($counts) === 0)
            ->set('text', str_repeat('x', 100001))->call('transform', 'upper')->assertHasErrors('text');
    }
}
