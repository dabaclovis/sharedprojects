<?php

namespace Tests\Feature;

use App\Livewire\Pages\RatingPrompt;
use App\Models\ApplicationRating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApplicationRatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_timer_continues_across_navigation_and_dismissal_stops_prompting(): void
    {
        $this->freezeTime();
        Livewire::test(RatingPrompt::class)->assertSet('eligible', true)->assertSet('delay', 91000);
        $this->travel(45)->seconds();
        Livewire::test(RatingPrompt::class)->assertSet('delay', 46000);
        $this->travel(46)->seconds();
        Livewire::test(RatingPrompt::class)->assertSet('delay', 0)->call('dismiss')->assertSet('eligible', false);
        Livewire::test(RatingPrompt::class)->assertSet('eligible', false);
        $this->assertDatabaseCount('application_ratings', 0);
    }

    public function test_guests_can_submit_once_only_after_ninety_seconds(): void
    {
        $this->freezeTime();
        $component = Livewire::test(RatingPrompt::class)->set('score', 5)->set('feedback', '  Very useful.  ');
        $component->call('submit')->assertHasErrors('submission');
        $this->travel(90)->seconds();
        $component->call('submit')->assertHasErrors('submission');
        $this->travel(1)->seconds();
        $component->call('submit')->assertHasNoErrors()->assertSet('submitted', true)->assertSee('Thank you for rating us!');
        $component->call('submit')->assertHasNoErrors();
        $this->assertDatabaseCount('application_ratings', 1);
        $this->assertDatabaseHas('application_ratings', ['user_id' => null, 'score' => 5, 'feedback' => 'Very useful.', 'ip_address' => '127.0.0.1']);
        Livewire::test(RatingPrompt::class)->assertSet('eligible', false);
    }

    public function test_invalid_ratings_and_feedback_are_rejected(): void
    {
        $component = Livewire::test(RatingPrompt::class);
        $this->travel(91)->seconds();
        $component->call('submit')->assertHasErrors('score')
            ->set('score', 6)->call('submit')->assertHasErrors('score')
            ->set('score', 2.5)->call('submit')->assertHasErrors('score')
            ->set('score', 4)->set('feedback', str_repeat('a', 1001))->call('submit')->assertHasErrors('feedback');
        $this->assertDatabaseCount('application_ratings', 0);
    }

    public function test_members_are_linked_to_their_rating_and_not_prompted_in_a_new_session(): void
    {
        $user = User::factory()->create();
        $component = Livewire::actingAs($user)->test(RatingPrompt::class);
        $this->travel(91)->seconds();
        $component->set('score', 4)->call('submit')->assertHasNoErrors();
        $this->assertDatabaseHas('application_ratings', ['user_id' => $user->id, 'score' => 4]);
        session()->forget('rating');
        Livewire::test(RatingPrompt::class)->assertSet('eligible', false);
    }

    public function test_feedback_is_private_to_admins_and_escaped(): void
    {
        ApplicationRating::create(['visitor_hash' => hash('sha256', 'visitor'), 'score' => 3, 'feedback' => '<script>alert(1)</script>']);
        $this->get(route('admins.ratings'))->assertRedirect(route('auth.login'));
        $this->actingAs(User::factory()->create())->get(route('admins.ratings'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admins.ratings'))
            ->assertOk()->assertSee('3.0 / 5')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('pages.index'))->assertOk()->assertSeeLivewire(RatingPrompt::class);
    }

    public function test_ipv6_is_captured_from_the_request_and_visible_only_to_admins(): void
    {
        $ip = '2001:db8::1234';
        $user = User::factory()->create();
        $this->actingAs($user);
        request()->server->set('REMOTE_ADDR', $ip);
        $component = new RatingPrompt;
        $component->mount();
        $this->travel(91)->seconds();
        $component->score = 5;
        $component->submit();
        $this->assertTrue($component->submitted);
        $rating = ApplicationRating::sole();
        $this->assertSame($ip, $rating->ip_address);
        $this->assertArrayNotHasKey('ip_address', $rating->toArray());
        $this->get(route('pages.index'))->assertDontSee($ip);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admins.ratings'))->assertOk()->assertSee($ip);
    }
}
