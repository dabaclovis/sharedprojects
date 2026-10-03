<div class="container py-5">
    <header class="dashboard-welcome p-4 mb-4">
        <p class="posts-eyebrow mb-2">Your earnings</p>
        <h1 class="h3 font-weight-bold">Withdraw post earnings</h1>
        <p class="text-muted mb-0">Request a payout from rewards earned through your posts. An admin will review and
            process it manually.</p>
    </header>
    @if (session('withdrawalStatus'))
    <div class="alert alert-success" role="status">{{ session('withdrawalStatus') }}</div>
    @endif
    <div class="row mb-4">
        <div class="col-sm-6 mb-3">
            <section class="dashboard-panel p-4 h-100">
                <p class="small text-muted mb-1">Total post earnings</p>
                <p class="h3 mb-0">${{ number_format($earnedCents / 100, 2) }}</p>
            </section>
        </div>
        <div class="col-sm-6 mb-3">
            <section class="dashboard-panel p-4 h-100">
                <p class="small text-muted mb-1">Available to withdraw</p>
                <p class="h3 mb-0">${{ number_format($availableCents / 100, 2) }}</p>
            </section>
        </div>
    </div>
    <section class="dashboard-panel p-4 mb-4" aria-labelledby="withdrawal-request-heading">
        <h2 id="withdrawal-request-heading" class="h5">Request a withdrawal</h2>
        <p class="small text-muted">Pending requests reserve their amount. Paid and pending requests are not available
            for another withdrawal.</p>
        <form wire:submit="submitRequest" novalidate>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="withdrawal-amount">Amount (USD)</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">$</span></div><input
                            id="withdrawal-amount" type="number" min="0.01" max="1000000" step="0.01"
                            inputmode="decimal" class="form-control @error('amount') is-invalid @enderror"
                            wire:model="amount" required aria-describedby="withdrawal-amount-error">
                    </div>
                    @error('amount')<p id="withdrawal-amount-error" class="text-danger small mt-1" role="alert">{{
                        $message }}</p>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label for="withdrawal-method">Payout method</label>
                    <select id="withdrawal-method" class="custom-select @error('payoutMethod') is-invalid @enderror"
                        wire:model="payoutMethod" required>
                        <option value="">Choose a method</option>
                        @foreach (\App\Models\WithdrawalRequest::METHODS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('payoutMethod')<p class="text-danger small mt-1" role="alert">{{ $message }}</p>@enderror
                </div>
                <div class="col-md-8 mb-3">
                    <label for="withdrawal-details">Payout destination</label>
                    <textarea id="withdrawal-details" class="form-control @error('payoutDetails') is-invalid @enderror"
                        wire:model="payoutDetails" rows="2" maxlength="1000" required
                        placeholder="Legal name, email or account name, and phone number"></textarea>
                    @error('payoutDetails')<p class="text-danger small mt-1" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>
            <p class="small text-muted">Your payout destination is encrypted and visible only to you and admins.</p>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                wire:target="submitRequest">Submit request</button>
        </form>
    </section>
    <section class="dashboard-panel p-4" aria-labelledby="withdrawal-history-heading">
        <h2 id="withdrawal-history-heading" class="h5">Withdrawal requests</h2>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th scope="col">Requested</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Method</th>
                        <th scope="col">Destination</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $request)
                    <tr wire:key="withdrawal-request-{{ $request->id }}">
                        <td>{{ $request->created_at->format('M j, Y') }}</td>
                        <td>${{ number_format($request->amount_cents / 100, 2) }}</td>
                        <td>{{ \App\Models\WithdrawalRequest::METHODS[$request->payout_method] ??
                            ucfirst($request->payout_method) }}</td>
                        <td style="overflow-wrap: anywhere;">{{ $request->payout_details }}</td>
                        <td><span
                                class="badge badge-{{ $request->status === 'paid' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">{{
                                ucfirst($request->status) }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">No withdrawal requests yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </section>
</div>