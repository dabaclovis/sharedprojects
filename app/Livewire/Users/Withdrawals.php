<?php

namespace App\Livewire\Users;

use App\Models\Reward;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'Withdraw Post Earnings | Brotherfall',
        'description' => 'Request withdrawal of your earned post rewards.',
        'keywords' => 'withdraw earnings, post rewards, payout request',
    ]
)]
class Withdrawals extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $amount = '';

    public string $payoutMethod = '';

    public string $payoutDetails = '';

    public function boot(): void
    {
        abort_unless(Auth::check() && Auth::user()->status === 'active', 403);
    }

    public function submitRequest(): void
    {
        $this->amount = trim($this->amount);
        $this->payoutDetails = trim($this->payoutDetails);
        $data = $this->validate([
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:1000000'],
            'payoutMethod' => ['required', Rule::in(array_keys(WithdrawalRequest::METHODS))],
            'payoutDetails' => ['required', 'string', 'min:3', 'max:1000'],
        ]);
        $amountCents = $this->toCents($data['amount']);

        $created = DB::transaction(function () use ($data, $amountCents) {
            $user = User::query()->lockForUpdate()->findOrFail(Auth::id());
            $earnedCents = Reward::query()->where('user_id', $user->id)
                ->where('content_type', 'post')->sum('amount_cents');
            $reservedCents = WithdrawalRequest::query()->where('user_id', $user->id)
                ->whereIn('status', ['pending', 'paid'])->sum('amount_cents');

            if ($amountCents > max(0, $earnedCents - $reservedCents)) {
                return false;
            }

            WithdrawalRequest::create([
                'user_id' => $user->id,
                'amount_cents' => $amountCents,
                'payout_method' => $data['payoutMethod'],
                'payout_details' => $data['payoutDetails'],
            ]);

            return true;
        });

        if (! $created) {
            $this->addError('amount', 'The requested amount exceeds your available post earnings.');

            return;
        }

        $this->reset('amount', 'payoutMethod', 'payoutDetails');
        $this->resetPage();
        session()->flash('withdrawalStatus', 'Withdrawal request submitted for admin review.');
    }

    private function toCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public function render()
    {
        $userId = Auth::id();
        $earnedCents = Reward::query()->where('user_id', $userId)
            ->where('content_type', 'post')->sum('amount_cents');
        $reservedCents = WithdrawalRequest::query()->where('user_id', $userId)
            ->whereIn('status', ['pending', 'paid'])->sum('amount_cents');

        return view('livewire.users.withdrawals', [
            'earnedCents' => (int) $earnedCents,
            'availableCents' => max(0, (int) $earnedCents - (int) $reservedCents),
            'requests' => WithdrawalRequest::query()->where('user_id', $userId)
                ->latest('id')->paginate(10),
        ]);
    }
}
