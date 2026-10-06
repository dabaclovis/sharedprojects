<?php

namespace App\Livewire\Admins;

use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'Withdrawal Requests | Brotherfall Admin',
        'description' => 'Review and manually process user withdrawal requests.',
        'keywords' => 'withdrawal requests, payout review, earnings administration',
    ]
)]
class Withdrawals extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $status = 'pending';

    public function boot(): void
    {
        abort_unless(auth()->user()?->role === 'admin' && auth()->user()?->status === 'active', 403);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function markPaid(int $id): void
    {
        $this->process($id, 'paid');
    }

    public function reject(int $id): void
    {
        $this->process($id, 'rejected');
    }

    private function process(int $id, string $status): void
    {
        $this->resetErrorBag('request');
        $processed = DB::transaction(function () use ($id, $status) {
            $request = WithdrawalRequest::query()->lockForUpdate()->findOrFail($id);
            if ($request->status !== 'pending') {
                return false;
            }

            $request->forceFill([
                'status' => $status,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ])->save();

            return true;
        });

        if (! $processed) {
            $this->addError('request', 'Only pending requests can be processed.');

            return;
        }

        session()->flash('withdrawalStatus', $status === 'paid' ? 'Withdrawal marked as paid.' : 'Withdrawal rejected.');
    }

    public function render()
    {
        $statuses = ['pending', 'paid', 'rejected'];
        $requests = WithdrawalRequest::query()->with('user:id,name,username')
            ->when(in_array($this->status, $statuses, true), fn($query) => $query->where('status', $this->status))
            ->latest('id')->paginate(15);

        return view('livewire.admins.withdrawals', [
            'requests' => $requests,
            'statuses' => $statuses,
        ]);
    }
}
