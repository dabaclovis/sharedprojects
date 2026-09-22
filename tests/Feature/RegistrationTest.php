<?php

namespace Tests\Feature;

use App\Livewire\Auths\Register;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_styling_only_appears_after_submission(): void
    {
        Livewire::test(Register::class)->assertDontSee('is-invalid', false)
            ->set('form.email', 'invalid')->assertHasNoErrors()->assertDontSee('is-invalid', false)
            ->call('register')->assertHasErrors(['form.name', 'form.email', 'form.password'])
            ->assertSee('is-invalid', false);
    }

    public function test_registration_page_is_available(): void
    {
        $this->get(route('auth.register'))->assertOk()->assertSee('Create an account');
    }

    public function test_guest_can_register_and_is_redirected_to_login_without_signing_in(): void
    {
        Event::fake([Registered::class]);

        Livewire::test(Register::class)
            ->set('form.name', ' clovis daba ')
            ->set('form.email', ' NEW@example.test ')
            ->set('form.password', 'secret-password')
            ->set('form.password_confirmation', 'secret-password')
            ->call('register')->assertHasNoErrors()
            ->assertSet('form.password', '')
            ->assertSet('form.password_confirmation', '')
            ->assertRedirect(route('auth.login'));

        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertSame('clovis daba', $user->name);
        $this->assertSame('cdaba01', $user->username);
        $this->assertSame('user', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertGuest();
        Event::assertDispatched(Registered::class, fn ($event) => $event->user->is($user));
        $this->get(route('auth.login'))->assertOk()->assertSee('cdaba01')->assertSee('Please sign in.');
    }

    public function test_generated_username_skips_existing_numbers_and_uses_last_name(): void
    {
        User::factory()->create(['username' => 'cdaba01']);
        User::factory()->create(['username' => 'cdaba02']);

        Livewire::test(Register::class)
            ->set('form.name', "  Clovis  \tDaba  ")->set('form.email', 'new@example.test')
            ->set('form.password', 'secret-password')
            ->set('form.password_confirmation', 'secret-password')
            ->call('register')->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['name' => 'Clovis Daba', 'email' => 'new@example.test', 'username' => 'cdaba03']);
        $this->assertGuest();
    }

    public function test_full_name_requires_exactly_two_words_on_submission(): void
    {
        foreach (['Clovis', 'Clovis Middle Daba'] as $name) {
            Livewire::test(Register::class)
                ->set('form.name', $name)->assertHasNoErrors()
                ->set('form.email', 'new@example.test')
                ->set('form.password', 'secret-password')
                ->set('form.password_confirmation', 'secret-password')
                ->call('register')->assertHasErrors('form.name')
                ->assertSee('The full name must contain exactly two words (first and last name).');
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_invalid_and_duplicate_values_do_not_create_an_account(): void
    {
        $existing = User::factory()->create(['username' => 'existing']);

        Livewire::test(Register::class)->call('register')
            ->assertHasErrors(['form.name', 'form.email', 'form.password'])
            ->set('form.name', 'New User')->set('form.email', 'invalid')
            ->set('form.password', 'short')->set('form.password_confirmation', 'short')
            ->call('register')->assertHasErrors(['form.email', 'form.password'])
            ->set('form.email', strtoupper($existing->email))
            ->set('form.password', 'secret-password')
            ->set('form.password_confirmation', 'different-password')
            ->call('register')->assertHasErrors([
                'form.email' => 'unique', 'form.password' => 'confirmed',
            ]);

        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_password_length_is_checked_in_bytes(): void
    {
        $password = str_repeat("\u{00e9}", 36).'A';

        Livewire::test(Register::class)
            ->set('form.name', 'New User')->set('form.email', 'new@example.test')
            ->set('form.password', $password)->set('form.password_confirmation', $password)
            ->call('register')->assertHasErrors('form.password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_attempts_are_rate_limited(): void
    {
        $component = Livewire::test(Register::class);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $component->call('register')->assertHasErrors('form.email');
        }

        $component->set('form.name', 'New User')->set('form.email', 'new@example.test')
            ->set('form.password', 'secret-password')
            ->set('form.password_confirmation', 'secret-password')
            ->call('register')->assertHasErrors('registration');

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_authenticated_users_are_redirected_and_cannot_register_again(): void
    {
        // These destination routes are planned; register them only for this test.
        Route::get('/test/users', fn () => 'Users')->name('users.index');
        Route::get('/test/admins', fn () => 'Admins')->name('admins.index');

        foreach (['user' => 'users.index', 'admin' => 'admins.index'] as $role => $destination) {
            Auth::logout();
            $component = Livewire::test(Register::class);
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get(route('auth.register'))->assertRedirect(route($destination));
            $this->get(route('auth.login'))->assertRedirect(route($destination));

            $component->call('register')->assertRedirect(route($destination));
            $this->assertAuthenticatedAs($user);
        }

        $this->assertDatabaseCount('users', 2);
    }
}
