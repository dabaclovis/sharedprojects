<?php

namespace Tests\Feature;

use App\Livewire\Pages\QuoteBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuoteBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_access_and_discovery(): void
    {
        $this->get(route('pages.quote-builder'))->assertOk()->assertSee('No account needed');
        $this->get('/')->assertSee(route('pages.quote-builder'));
        $this->assertGuest();
    }

    public function test_itemized_estimate_deposit_and_export(): void
    {
        Livewire::test(QuoteBuilder::class)->set('project', 'Bakery website')
            ->set('tasks', [
                ['description' => 'Design', 'hours' => '2.5', 'rate' => '80'],
                ['description' => 'Revisions', 'hours' => '1', 'rate' => '50'],
            ])->set('expenses', '25')->set('buffer', '10')->set('deposit', '30')
            ->set('scope', 'Two revisions included.')
            ->call('calculate')->assertHasNoErrors()
            ->assertSet('result.labor', 25000)->assertSet('result.buffer', 2500)
            ->assertSet('result.total', 30000)->assertSet('result.deposit', 9000)
            ->assertSet('result.balance', 21000)->assertSee('Two revisions included.')
            ->call('download')->assertFileDownloaded('project-estimate.txt')
            ->set('expenses', '50')->assertSet('result', null);
    }

    public function test_rounding_and_full_deposit(): void
    {
        Livewire::test(QuoteBuilder::class)->set('project', 'Small task')
            ->set('tasks', [['description' => 'Work', 'hours' => '0.5', 'rate' => '0.03']])
            ->set('deposit', '100')->call('calculate')->assertHasNoErrors()
            ->assertSet('result.total', 2)->assertSet('result.deposit', 2)->assertSet('result.balance', 0);
    }

    public function test_validation_and_task_controls(): void
    {
        $component = Livewire::test(QuoteBuilder::class)
            ->call('calculate')->assertHasErrors(['project', 'tasks.0.description'])
            ->set('project', 'Project')->set('tasks.0.description', 'Work')
            ->set('tasks.0.hours', '-1')->set('currency', 'INVALID')->set('deposit', '101')
            ->call('calculate')->assertHasErrors(['tasks.0.hours', 'currency', 'deposit'])
            ->assertSet('result', null)
            ->call('addTask')->assertCount('tasks', 2)
            ->call('removeTask', 0)->assertCount('tasks', 1)
            ->call('removeTask', 0)->assertCount('tasks', 1);
        $component->set('tasks', array_fill(0, 20, ['description' => 'Task', 'hours' => '1', 'rate' => '50']))
            ->call('addTask')->assertCount('tasks', 20);
    }
}
