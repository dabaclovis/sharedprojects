<div class="container py-4">
    <header class="admin-welcome p-4 mb-4">
        <p class="small text-uppercase font-weight-bold">Administration</p>
        <h1 class="h3">Withdrawal requests</h1>
        <p class="mb-0">Review payout destinations and record manual payments.</p>
    </header>
    @if (session('withdrawalStatus'))<div class="alert alert-success" role="status">{{ session('withdrawalStatus') }}
    </div>@endif
    @error('request')<div class="alert alert-warning" role="alert">{{ $message }}</div>@enderror
    <section class="dashboard-panel p-4">
        <div class="row align-items-end mb-3">
            <div class="col-md-4">
                <label for="withdrawal-status">Status</label>
                <select id="withdrawal-status" class="custom-select" wire:model.live="status">
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="rejected">Rejected</option>
                    <option value="all">All requests</option>
                </select>
            </div>
            <p class="col-md-8 small text-muted mb-2 text-md-right">Payouts are sent manually outside the application.
            </p>
        </div>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th scope="col">User</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Payout method</th>
                        <th scope="col">Destination</th>
                        <th scope="col">Requested</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $request)
                    <tr wire:key="admin-withdrawal-{{ $request->id }}">
                        <td>{{ $request->user?->username ?: ($request->user?->name ?? 'Deleted account') }}</td>
                        <td>${{ number_format($request->amount_cents / 100, 2) }}</td>
                        <td>{{ \App\Models\WithdrawalRequest::METHODS[$request->payout_method] ??
                            ucfirst($request->payout_method) }}</td>
                        <td style="overflow-wrap: anywhere;">{{ $request->payout_details }}</td>
                        <td>{{ $request->created_at->format('M j, Y') }}<br><span class="small text-muted">{{
                                ucfirst($request->status) }}</span></td>
                        <td class="text-nowrap">
                            @if ($request->status === 'pending')
                            <button type="button" class="btn btn-success btn-sm"
                                wire:click="markPaid({{ $request->id }})"
                                wire:confirm="Confirm this withdrawal has been paid?" wire:loading.attr="disabled">Mark
                                paid</button>
                            <button type="button" class="btn btn-outline-danger btn-sm"
                                wire:click="reject({{ $request->id }})" wire:confirm="Reject this withdrawal request?"
                                wire:loading.attr="disabled">Reject</button>
                            @else
                            {{ $request->processed_at?->format('M j, Y') ?? '—' }}
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No withdrawal requests found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </section>
</div>