<?php

namespace Tests\Feature;

use App\Livewire\Admins\Rewards;
use App\Livewire\Pages\Notes;
use App\Models\Post;
use App\Models\Quote;
use App\Models\Reward;
use App\Models\RewardFund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RewardsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'user'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_only_admins_can_open_and_fund_rewards(): void
    {
        $this->get(route('admins.rewards'))->assertRedirect(route('auth.login'));
        $this->actingAs($this->user())->get(route('admins.rewards'))->assertForbidden();

        $admin = $this->user('admin');
        Livewire::actingAs($admin)->test(Rewards::class)
            ->set('deposit', '50')->call('depositFunds')->assertHasNoErrors()
            ->assertSee('Reward fund increased by $50.00.');

        $this->assertSame(5000, RewardFund::current()->balance_cents);
    }

    public function test_signed_in_quote_can_receive_one_twenty_five_dollar_reward(): void
    {
        $member = $this->user();
        Livewire::actingAs($member)->test(Notes::class)
            ->set('content', 'A reward-worthy thought.')->call('save')->assertHasNoErrors();
        $quote = Quote::sole();
        $this->assertTrue($quote->user->is($member));

        $admin = $this->user('admin');
        RewardFund::current()->update(['balance_cents' => 5000]);
        $component = Livewire::actingAs($admin)->test(Rewards::class)
            ->assertSee('A reward-worthy thought.')
            ->call('award', 'quote', $quote->id)->assertHasNoErrors();

        $this->assertDatabaseHas('rewards', [
            'user_id' => $member->id,
            'content_type' => 'quote',
            'content_id' => $quote->id,
            'amount_cents' => 2500,
        ]);
        $this->assertSame(2500, RewardFund::current()->balance_cents);

        $component->call('award', 'quote', $quote->id)->assertHasErrors('reward');
        $this->assertSame(1, Reward::count());
    }

    public function test_published_post_with_350_to_600_words_and_category_can_receive_one_dollar(): void
    {
        $member = $this->user();
        $post = new Post([
            'title' => 'A useful qualified article for everyone',
            'slug' => fake()->uuid(),
            'content' => implode(' ', array_fill(0, 350, 'word')),
            'category' => 'Life',
        ]);
        $post->author()->associate($member);
        $post->postsable()->associate($member);
        $post->forceFill(['status' => 'published', 'published_at' => now()])->save();

        $admin = $this->user('admin');
        RewardFund::current()->update(['balance_cents' => 100]);
        Livewire::actingAs($admin)->test(Rewards::class)
            ->assertSee('A useful qualified article for everyone')->assertSee('6 title words')->assertSee('350')
            ->call('award', 'post', $post->id)->assertHasNoErrors();

        $this->assertDatabaseHas('rewards', [
            'user_id' => $member->id,
            'content_type' => 'post',
            'content_id' => $post->id,
            'amount_cents' => 100,
        ]);
        $this->assertSame(0, RewardFund::current()->balance_cents);
    }

    public function test_guest_quotes_ineligible_posts_and_insufficient_funds_are_not_rewarded(): void
    {
        Quote::create(['content' => 'Anonymous quote']);
        $member = $this->user();
        $post = new Post([
            'title' => 'Too short',
            'slug' => fake()->uuid(),
            'content' => 'Only a few words',
            'category' => 'Life',
        ]);
        $post->author()->associate($member);
        $post->postsable()->associate($member);
        $post->forceFill(['status' => 'published', 'published_at' => now()])->save();

        $admin = $this->user('admin');
        $component = Livewire::actingAs($admin)->test(Rewards::class)
            ->assertDontSee('Anonymous quote')->assertDontSee('Too short');
        $component->call('award', 'post', $post->id)->assertStatus(422);

        $quote = Quote::create(['content' => 'Linked but unfunded', 'user_id' => $member->id]);
        Livewire::actingAs($admin)->test(Rewards::class)
            ->call('award', 'quote', $quote->id);
        $this->assertDatabaseCount('rewards', 0);
        $this->assertSame(0, RewardFund::current()->balance_cents);
    }
}
