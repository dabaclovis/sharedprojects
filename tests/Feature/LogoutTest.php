<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_clears_authentication_and_session_and_rotates_csrf_token(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['private_data' => 'example', '_token' => 'old-token']);
        $sessionId = session()->getId();

        $this->post(route('auth.logout'))->assertRedirect(route('pages.index'))
            ->assertSessionMissing('private_data');

        $this->assertGuest('web');
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotEmpty(session()->token());
        $this->assertNotSame('old-token', session()->token());
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->post(route('auth.logout'))->assertRedirect(route('auth.login'));
    }

    public function test_logout_requires_a_post_request(): void
    {
        $this->actingAs(User::factory()->create())->get(route('auth.logout'))->assertStatus(405);
        $this->assertAuthenticated();
    }

    public function test_authenticated_layout_logs_out_after_four_minutes_of_inactivity(): void
    {
        $this->actingAs(User::factory()->create())->get(route('users.index'))->assertOk()
            ->assertSee('const idleTimeout = 4 * 60 * 1000;', false)
            ->assertSee('id="account-logout-form"', false)
            ->assertSee('logoutForm.requestSubmit()', false);

        auth()->logout();
        $this->get(route('pages.index'))->assertOk()
            ->assertDontSee('const idleTimeout = 4 * 60 * 1000;', false);
    }
}
