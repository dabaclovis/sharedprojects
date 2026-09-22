<?php

namespace Tests\Feature;

use App\Livewire\Users\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_accounts_can_access_settings(): void
    {
        $this->get(route('users.setting'))->assertRedirect(route('auth.login'));
        $this->actingAs(User::factory()->create(['status' => 'inactive']))->get(route('users.setting'))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('users.setting'))->assertOk();
    }

    public function test_profile_requires_password_and_preserves_username_and_privileges(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $originalUsername = $user->username;
        $component = Livewire::actingAs($user)->test(Setting::class)
            ->set('name', ' New Name ')->set('email', 'NEW@example.test')
            ->set('currentPassword', 'wrong')->call('saveProfile')->assertHasErrors('currentPassword');
        $this->assertNotSame('new@example.test', $user->fresh()->email);
        $component->set('currentPassword', 'old-password')->call('saveProfile')->assertHasNoErrors();
        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.test', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertSame($originalUsername, $user->username);
        $this->assertSame('user', $user->role);
    }

    public function test_profile_rejects_duplicate_email_and_non_two_word_name(): void
    {
        $other = User::factory()->create();
        Livewire::actingAs(User::factory()->create())->test(Setting::class)
            ->set('name', 'Single')->set('email', $other->email)->set('currentPassword', 'password')
            ->call('saveProfile')->assertHasErrors(['name', 'email']);
    }

    public function test_password_update_validates_confirmation_and_hashes_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password', 'remember_token' => 'old-token']);
        $component = Livewire::actingAs($user)->test(Setting::class)
            ->set('passwordCurrent', 'old-password')->set('password', 'new-password')
            ->set('password_confirmation', 'mismatch')->call('savePassword')->assertHasErrors('password');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $component->set('password_confirmation', 'new-password')->call('savePassword')
            ->assertHasNoErrors()->assertSet('password', '')->assertSet('passwordCurrent', '');
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertNotSame('old-token', $user->fresh()->remember_token);
    }

    public function test_admin_dashboard_rejects_guests_regular_and_inactive_accounts(): void
    {
        $this->get(route('admins.index'))->assertRedirect(route('auth.login'));
        $this->actingAs(User::factory()->create())->get(route('admins.index'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'inactive']))->get(route('admins.index'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admins.index'))->assertOk();
    }
}
