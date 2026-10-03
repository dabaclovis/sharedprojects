<?php

namespace Tests\Feature;

use App\Livewire\Admins\Withdrawals as AdminWithdrawals;
use App\Livewire\Users\Withdrawals as UserWithdrawals;
use App\Models\Reward;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class WithdrawalsTest extends TestCase
{
    use RefreshDatabase;

    private function addPostEarnings(User $user, int $amountCents): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reward = new Reward([
            'content_type' => 'post',
            'content_id' => fake()->unique()->numberBetween(1, 100000),
            'amount_cents' => $amountCents,
        ]);
        $reward->user()->associate($user);
        $reward->awardedBy()->associate($admin);
        $reward->save();
    }

    public function test_user_can_request_withdrawal_and_pending_amount_is_reserved(): void
    {
        $user = User::factory()->create();
        $this->addPostEarnings($user, 500);

        $component = Livewire::actingAs($user)->test(UserWithdrawals::class)
            ->assertSee('$5.00')->assertSee('Legal name, email or account name, and phone number')
            ->set('amount', '3.25')
            ->set('payoutMethod', 'paypal')
            ->set('payoutDetails', 'Alex Morgan, payout@example.test, +1 (555) 123-4567')
            ->call('submitRequest')->assertHasNoErrors()->assertSee('Withdrawal request submitted');

        $request = WithdrawalRequest::sole();
        $this->assertSame(325, $request->amount_cents);
        $this->assertSame('Alex Morgan, payout@example.test, +1 (555) 123-4567', $request->payout_details);
        $this->assertNotSame($request->payout_details, DB::table('withdrawal_requests')->value('payout_details'));
        $component->assertSee('$1.75');
    }

    public function test_user_cannot_request_more_than_unreserved_earnings(): void
    {
        $user = User::factory()->create();
        $this->addPostEarnings($user, 500);
        $component = Livewire::actingAs($user)->test(UserWithdrawals::class)
            ->set('amount', '4.00')
            ->set('payoutMethod', 'bank_transfer')
            ->set('payoutDetails', 'Account ending 1234')->call('submitRequest')->assertHasNoErrors();

        $component->set('amount', '1.01')
            ->set('payoutMethod', 'bank_transfer')
            ->set('payoutDetails', 'Account ending 1234')
            ->call('submitRequest')->assertHasErrors('amount');
        $this->assertDatabaseCount('withdrawal_requests', 1);
    }

    public function test_admin_can_mark_paid_or_reject_and_only_admins_can_manage_requests(): void
    {
        $user = User::factory()->create();
        $this->addPostEarnings($user, 1000);
        $request = WithdrawalRequest::create([
            'user_id' => $user->id,
            'amount_cents' => 300,
            'payout_method' => 'paypal',
            'payout_details' => 'Alex Morgan, payout@example.test, +1 555 123 4567',
        ]);
        $otherRequest = WithdrawalRequest::create([
            'user_id' => $user->id,
            'amount_cents' => 200,
            'payout_method' => 'bank_transfer',
            'payout_details' => 'Alex Morgan, account ending 1234, +1 555 123 4567',
        ]);

        $this->get(route('admins.withdrawals'))->assertRedirect(route('auth.login'));
        $this->actingAs(User::factory()->create())->get(route('admins.withdrawals'))->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        Livewire::actingAs($admin)->test(AdminWithdrawals::class)
            ->assertSee('Alex Morgan, payout@example.test, +1 555 123 4567')
            ->call('markPaid', $request->id)->assertSee('Withdrawal marked as paid.')
            ->call('reject', $otherRequest->id)->assertSee('Withdrawal rejected.');

        $this->assertDatabaseHas('withdrawal_requests', [
            'id' => $request->id,
            'status' => 'paid',
            'processed_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('withdrawal_requests', [
            'id' => $otherRequest->id,
            'status' => 'rejected',
            'processed_by' => $admin->id,
        ]);
    }
}
