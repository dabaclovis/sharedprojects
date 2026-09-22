<?php

namespace Tests\Feature;

use App\Livewire\Services\Users;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_only_active_admins_can_access_users(): void
    {
        $this->get(route('services.users'))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('services.users'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'inactive']))
            ->get(route('services.users'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('services.users'))->assertOk();
    }

    public function test_admin_can_create_a_user_with_a_hashed_password(): void
    {
        Livewire::actingAs($this->admin())->test(Users::class)
            ->call('create')
            ->set('form.name', ' New User ')
            ->set('form.username', 'new_user')
            ->set('form.email', 'NEW@example.test')
            ->set('form.password', 'secret-password')
            ->set('form.role', 'admin')
            ->set('form.status', 'inactive')
            ->call('save')->assertHasNoErrors()->assertSet('showForm', false);

        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertSame('New User', $user->name);
        $this->assertSame('admin', $user->role);
        $this->assertSame('inactive', $user->status);
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_password_limit_is_checked_in_bytes_on_create_and_edit(): void
    {
        $user = User::factory()->create();
        $originalPassword = $user->password;
        $password = str_repeat('é', 36);

        $component = Livewire::actingAs($this->admin())->test(Users::class)
            ->call('create')->set('form.name', 'Byte Test')
            ->set('form.email', 'bytes@example.test')->set('form.password', $password.'A')
            ->call('save')->assertHasErrors('form.password');
        $this->assertDatabaseMissing('users', ['email' => 'bytes@example.test']);

        $component->call('edit', $user->id)->set('form.password', $password.'A')
            ->call('save')->assertHasErrors('form.password');
        $this->assertSame($originalPassword, $user->fresh()->password);

        $component->set('form.password', $password)->call('save')->assertHasNoErrors();
        $this->assertTrue(Hash::check($password, $user->fresh()->password));
    }

    public function test_validation_rejects_duplicates_and_invalid_values(): void
    {
        $existing = User::factory()->create();
        Livewire::actingAs($this->admin())->test(Users::class)
            ->call('create')->call('save')
            ->assertHasErrors(['form.name', 'form.email', 'form.password'])
            ->set('form.name', 'Duplicate')
            ->set('form.username', $existing->username)
            ->set('form.email', $existing->email)
            ->set('form.password', 'short')
            ->set('form.role', 'owner')->set('form.status', 'unknown')
            ->call('save')->assertHasErrors(['form.username', 'form.email', 'form.password', 'form.role', 'form.status']);
    }

    public function test_edit_preserves_blank_password_and_resets_email_verification(): void
    {
        $user = User::factory()->create();
        $password = $user->password;
        $component = Livewire::actingAs($this->admin())->test(Users::class)
            ->call('edit', $user->id)->assertSet('form.password', '')
            ->call('save')->assertHasNoErrors();
        $this->assertNotNull($user->fresh()->email_verified_at);

        $component->call('edit', $user->id)
            ->set('form.email', 'changed@example.test')->set('form.username', '')
            ->call('save')->assertHasNoErrors();
        $this->assertSame($password, $user->fresh()->password);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->username);

        $component->call('edit', $user->id)->set('form.password', 'replacement-password')
            ->call('save')->assertHasNoErrors();
        $this->assertTrue(Hash::check('replacement-password', $user->fresh()->password));
    }

    public function test_admin_can_delete_another_user_but_cannot_remove_own_access(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $component = Livewire::actingAs($admin)->test(Users::class)
            ->call('edit', $user->id)->call('delete', $user->id)
            ->assertSet('showForm', false)->assertHasNoErrors();
        $this->assertModelMissing($user);

        $component->call('delete', $admin->id)->assertHasErrors('delete')
            ->call('edit', $admin->id)->set('form.role', 'user')
            ->call('save')->assertHasErrors('form.role')
            ->set('form.role', 'admin')->set('form.status', 'inactive')
            ->call('save')->assertHasErrors('form.role');
        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertSame('active', $admin->fresh()->status);
    }

    public function test_search_resets_pagination_and_cancel_clears_form(): void
    {
        $admin = $this->admin();
        User::factory()->count(12)->create();
        $target = User::factory()->create(['name' => 'Unique Search Person']);

        Livewire::actingAs($admin)->test(Users::class)
            ->call('setPage', 2)->set('search', 'Unique Search Person')
            ->assertSet('paginators.page', 1)->assertSee($target->email)->assertDontSee($admin->email)
            ->call('edit', $target->id)->set('form.password', 'unsaved-password')
            ->call('cancel')->assertSet('form.userId', null)->assertSet('form.password', '')
            ->assertSet('showForm', false);
    }

    public function test_permissions_are_checked_on_livewire_updates(): void
    {
        $admin = $this->admin();
        $component = Livewire::actingAs($admin)->test(Users::class);
        $admin->status = 'inactive';
        $admin->save();

        $component->call('create')->assertForbidden();
    }

    public function test_form_user_id_cannot_be_changed_by_the_client(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $component = Livewire::actingAs($admin)->test(Users::class)
            ->call('edit', $user->id);

        $this->expectException(CannotUpdateLockedPropertyException::class);

        $component->set('form.userId', $admin->id);
    }
}
