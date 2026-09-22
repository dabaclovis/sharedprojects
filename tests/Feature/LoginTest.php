<?php

namespace Tests\Feature;

use App\Livewire\Auths\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_styling_only_appears_after_submission(): void
    {
        Livewire::test(Login::class)->assertDontSee('is-invalid', false)
            ->set('form.identifier', '')->assertHasNoErrors()->assertDontSee('is-invalid', false)
            ->call('login')->assertHasErrors(['form.identifier', 'form.password'])
            ->assertSee('is-invalid', false);
    }

    public function test_login_view_contains_the_form_and_registration_link(): void
    {
        $this->get(route('auth.login'))->assertOk()->assertSee('Email or username')
            ->assertSee('Remember me')->assertSee(route('auth.register'));
    }

    public function test_users_can_login_by_email_or_username_and_redirect_by_role(): void
    {
        foreach (['user' => 'email', 'admin' => 'username'] as $role => $field) {
            Auth::logout();
            $user = User::factory()->create(['role' => $role, 'username' => $role.'01']);
            $sessionId = session()->getId();

            Livewire::test(Login::class)->set('form.identifier', $user->{$field})
                ->set('form.password', 'password')->set('form.remember', true)
                ->call('login')->assertHasNoErrors()->assertSet('form.password', '')
                ->assertRedirect(route($role === 'admin' ? 'admins.index' : 'users.index'));

            $this->assertAuthenticatedAs($user);
            $this->assertNotSame($sessionId, session()->getId());
        }
    }

    public function test_invalid_credentials_and_inactive_accounts_are_rejected(): void
    {
        $user = User::factory()->create();
        $component = Livewire::test(Login::class)->call('login')
            ->assertHasErrors(['form.identifier', 'form.password'])
            ->set('form.identifier', $user->email)->set('form.password', 'wrong-password')
            ->call('login')->assertHasErrors('form.identifier');
        $this->assertGuest();

        $user->status = 'inactive';
        $user->save();
        $component->set('form.password', 'password')->call('login')->assertHasErrors('form.identifier');
        $this->assertGuest();
    }

    public function test_login_attempts_are_limited(): void
    {
        $user = User::factory()->create();
        $component = Livewire::test(Login::class)->set('form.identifier', $user->email)
            ->set('form.password', 'wrong-password');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $component->call('login')->assertHasErrors('form.identifier');
        }

        $component->set('form.password', 'password')->call('login')->assertHasErrors('login');
        $this->assertGuest();
    }
}
