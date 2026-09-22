<?php

namespace Tests\Feature;

use App\Livewire\Auths\Login;
use App\Livewire\Services\Users;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuspensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_must_provide_a_reason_and_can_reactivate(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $component = Livewire::actingAs($admin)->test(Users::class)->call('edit', $user->id)
            ->set('form.status', 'suspended')->call('save')->assertHasErrors('form.suspension_reason')
            ->set('form.suspension_reason', 'Repeated abusive language.')->call('save')->assertHasNoErrors();
        $this->assertSame('suspended', $user->fresh()->status);
        $this->assertSame('Repeated abusive language.', $user->fresh()->suspension_reason);
        $component->call('edit', $user->id)->set('form.status', 'active')->call('save')->assertHasNoErrors();
        $this->assertNull($user->fresh()->suspension_reason);
    }

    public function test_reason_is_only_disclosed_after_correct_credentials(): void
    {
        $user = User::factory()->create(['status' => 'suspended', 'suspension_reason' => 'Repeated abusive language.']);
        Livewire::test(Login::class)->set('form.identifier', $user->email)
            ->set('form.password', 'wrong-password')->call('login')->assertDontSee('Repeated abusive language.')
            ->set('form.password', 'password')->call('login')->assertHasErrors('form.identifier')
            ->assertSee('Repeated abusive language.')->assertSee('info@myapp.com');
        $this->assertGuest();
    }

    public function test_suspended_sessions_see_reason_and_can_logout(): void
    {
        $user = User::factory()->create(['status' => 'suspended', 'suspension_reason' => 'Unacceptable language <script>alert(1)</script>']);
        $this->actingAs($user)->get(route('users.index'))->assertForbidden()
            ->assertSee('Unacceptable language')->assertSee('info@myapp.com')->assertDontSee('<script>alert(1)</script>', false);
        $this->post(route('auth.logout'))->assertRedirect();
        $this->assertGuest();
    }
}
