<div class="py-4">
    <header class="admin-welcome p-4 mb-4"><p class="small text-uppercase font-weight-bold">Revenue services</p><h1 class="h3">{{ $serviceTitle }}</h1><p class="mb-0">Manage requests, agree a quote, record payment, and deliver the service.</p></header>
    <nav class="d-flex flex-wrap mb-4" style="gap: .5rem;" aria-label="Revenue services"><a wire:navigate class="btn {{ $service === 'website-audit' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admins.website-audits') }}">Website audits</a><a wire:navigate class="btn {{ $service === 'sponsorship' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admins.sponsorships') }}">Sponsorships</a><a wire:navigate class="btn btn-light" href="{{ route('pages.business') }}">Customer request page</a></nav>
    @if (session('serviceStatus'))<div class="alert alert-success" role="status">{{ session('serviceStatus') }}</div>@endif
    @if ($service === 'website-audit')
        <section class="dashboard-panel p-4 mb-4" aria-labelledby="audit-workflow-heading">
            <h2 id="audit-workflow-heading" class="h5">Deliver a website audit</h2>
            <p class="text-muted">Open a customer request below, agree a quote, record payment, then generate findings and write the client's action plan.</p>
            <div class="d-flex flex-wrap" style="gap: .5rem;">
                <a wire:navigate class="btn btn-primary btn-sm" href="{{ route('pages.business') }}">New audit request</a>
                <a wire:navigate class="btn btn-outline-primary btn-sm" href="{{ route('pages.seo-audit') }}">Open free SEO audit tool</a>
            </div>
        </section>
    @endif
    <div class="row mb-3"><div class="col-md-4 mb-3"><div class="dashboard-panel p-3"><h2 class="h6">New requests</h2><strong class="h3">{{ $newCount }}</strong></div></div><div class="col-md-8 mb-3"><div class="dashboard-panel p-3"><h2 class="h6">Recorded receipts</h2>@forelse ($paidTotals as $code => $total)<strong class="h4 mr-3">{{ $code }} {{ number_format($total / 100, 2) }}</strong>@empty<p class="mb-0 text-muted">No verified payments recorded yet.</p>@endforelse<p class="small text-muted mb-0 mt-2">Manual payment records, excluding full refunds. Before fees and taxes; currencies are kept separate.</p></div></div></div>
    <section class="dashboard-panel p-4 mb-4" aria-label="Service requests">
        <div class="row"><div class="col-md-8 mb-3"><label for="order-search">Search name, email, or reference</label><input id="order-search" type="search" class="form-control" wire:model.live.debounce.300ms="search" maxlength="200"></div><div class="col-md-4 mb-3"><label for="order-status">Status</label><select id="order-status" class="custom-select" wire:model.live="status"><option value="">All statuses</option>@foreach (\App\Models\ServiceOrder::STATUSES as $state)<option value="{{ $state }}">{{ ucfirst(str_replace('_', ' ', $state)) }}</option>@endforeach</select></div></div>
        <div class="d-flex justify-content-between align-items-center mb-2"><p class="small text-muted mb-0" role="status">{{ $orders->total() }} requests found</p>@if ($search !== '' || $status !== '')<button type="button" class="btn btn-link btn-sm" wire:click="clearFilters">Clear filters</button>@endif</div>
        <p class="small text-muted" wire:loading.delay wire:target="search,status,clearFilters,open,gotoPage,nextPage,previousPage" role="status">Loading requests…</p>
        <div class="table-responsive"><table class="table"><thead><tr><th scope="col">Customer</th><th scope="col">Quote</th><th scope="col">Progress</th><th scope="col">Received</th><th scope="col">Action</th></tr></thead><tbody>@forelse ($orders as $row)<tr wire:key="order-{{ $row->id }}"><td><strong>{{ $row->name }}</strong><br><span class="small">{{ $row->email }}</span><br><span class="small text-muted">{{ $row->reference }}</span></td><td>{{ $row->price() }}<br><span class="badge badge-light">{{ ucfirst($row->payment_status) }}</span></td><td>{{ ucfirst(str_replace('_', ' ', $row->status)) }}</td><td>{{ $row->created_at->format('M j, Y') }}</td><td><button class="btn btn-outline-primary btn-sm" wire:click="open({{ $row->id }})" wire:loading.attr="disabled">Manage</button></td></tr>@empty<tr><td colspan="5" class="text-center py-4">No requests match these filters. Share the customer request page to invite inquiries.</td></tr>@endforelse</tbody></table></div>
        {{ $orders->links(data: ['scrollTo' => false]) }}
    </section>
    @if ($order)
    <section class="dashboard-panel p-4" aria-labelledby="manage-order-heading" wire:key="manage-order-{{ $order->id }}" x-data x-init="$nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'start' }))" style="scroll-margin-top: 6rem;">
        <div class="d-flex justify-content-between"><div><h2 id="manage-order-heading" class="h4">{{ $order->name }}</h2><p class="small text-muted">Reference {{ $order->reference }}</p></div><button class="btn btn-light align-self-start" wire:click="close">Close</button></div>
        @if ($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <p><strong>Contact:</strong> {{ $order->email }}<br><strong>Website:</strong> <span style="overflow-wrap: anywhere;">{{ $order->website }}</span></p><p style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $order->brief }}</p>
        <p><span class="badge badge-primary">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span> <span class="badge badge-light">{{ ucfirst($order->payment_status) }}</span> <strong>{{ $order->price() }}</strong></p>
        <div class="row">
            <div class="col-lg-5">
                <h3 class="h5">Quote and payment</h3>
                @if (in_array($order->status, ['new', 'quoted']) && $order->payment_status === 'unpaid')
                    <form wire:submit="quote" class="mb-3"><div class="row"><div class="col-7"><label for="order-amount">Agreed price</label><input id="order-amount" class="form-control" wire:model="amount" inputmode="decimal" placeholder="0.00" required></div><div class="col-5"><label for="order-currency">Currency</label><select id="order-currency" class="custom-select" wire:model="currency">@foreach (['USD', 'EUR', 'GBP', 'INR', 'NGN', 'KES', 'ZAR'] as $code)<option value="{{ $code }}">{{ $code }}</option>@endforeach</select></div></div><button class="btn btn-outline-primary btn-sm mt-2" wire:loading.attr="disabled">Save quote</button></form>
                @endif
                <p class="small text-muted">Agree the scope and price with the customer, then collect payment through your bank or payment provider. These controls record payments; they do not move money or send messages.</p>
                @if ($order->status === 'quoted' && $order->payment_status === 'unpaid')
                    <form wire:submit="recordPayment" class="mb-3" wire:confirm="Have you verified receipt of the full quoted amount in your bank or payment provider?"><label for="payment-reference">Bank or provider transaction reference</label><input id="payment-reference" class="form-control" wire:model="paymentReference" maxlength="255" required><button class="btn btn-success btn-sm mt-2" wire:loading.attr="disabled">Record verified payment</button></form>
                @endif
                @if ($order->payment_reference)<p class="small">Payment reference: {{ $order->payment_reference }}<br>Recorded {{ $order->paid_at?->utc()->format('M j, Y H:i') }} UTC</p>@endif
                @if ($order->payment_status === 'paid')<button class="btn btn-outline-danger btn-sm mb-3" wire:click="recordRefund" wire:confirm="Confirm that you have already issued a full refund outside this application. This will remove the placement or revoke report access." wire:loading.attr="disabled">Record full refund</button>@elseif ($order->payment_status === 'unpaid' && in_array($order->status, ['new', 'quoted']))<button class="btn btn-outline-danger btn-sm mb-3" wire:click="cancelOrder" wire:confirm="Cancel this unpaid request? Its history will be retained." wire:loading.attr="disabled">Cancel request</button>@endif
                <form wire:submit="saveNotes"><label for="order-notes">Internal notes</label><textarea id="order-notes" class="form-control" wire:model="internalNotes" rows="4" maxlength="10000"></textarea><p class="small text-muted mt-1">Visible only to admins.</p><button class="btn btn-outline-secondary btn-sm" wire:loading.attr="disabled">Save notes</button></form>
            </div>
            <div class="col-lg-7 mt-4 mt-lg-0">
                @if ($service === 'website-audit')
                    <h3 class="h5">Audit delivery</h3>
                    @if ($order->status === 'quoted' && $order->payment_status === 'paid')<button class="btn btn-primary" wire:click="startAudit" wire:loading.attr="disabled">Start audit</button>@endif
                    @if ($order->status === 'in_progress')<p class="small text-muted">Run a check of the requested page, review the findings, and write a practical action plan. JavaScript is not executed; response time is not a browser performance score.</p><button class="btn btn-outline-primary mb-3" wire:click="runAudit" wire:loading.attr="disabled"><span wire:loading.remove wire:target="runAudit">{{ $order->audit_result ? 'Refresh page findings' : 'Generate page findings' }}</span><span wire:loading wire:target="runAudit">Checking page…</span></button>@endif
                    @if ($order->audit_result)
                        <div class="border rounded p-3 mb-3"><h4 class="h6">Automated findings</h4><p class="small text-muted">Checked {{ $order->audit_result['checked_at'] ?? '' }}</p><ul class="mb-0">@forelse ($order->audit_result['issues'] ?? [] as $issue)<li><strong>{{ $issue['priority'] }}:</strong> {{ $issue['message'] }}</li>@empty<li>No issues flagged by the automated checks. Review manually before delivery.</li>@endforelse</ul></div>
                    @endif
                    @if ($order->status === 'in_progress')
                        <form wire:submit="saveReport"><label for="audit-deliverable">Client action plan</label><textarea id="audit-deliverable" class="form-control" wire:model="deliverable" rows="10" maxlength="30000" placeholder="Explain the findings, list prioritized changes, and describe what was outside the review scope." required></textarea><div class="d-flex flex-wrap mt-3" style="gap: .5rem;"><button class="btn btn-outline-primary" wire:loading.attr="disabled">Save draft</button><button type="button" class="btn btn-primary" wire:click="completeAudit" wire:confirm="Mark this report as reviewed and ready for the client? The completed report will be locked." wire:loading.attr="disabled">Complete report</button></div></form>
                    @elseif ($order->deliverable)<p style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $order->deliverable }}</p>@endif
                    @if ($reportUrl)<div class="alert alert-success mt-3"><h4 class="h6">Ready for delivery</h4><p class="small">Share this private link with the customer. Anyone with it can view the report for 30 days. Open it to print or save as PDF.</p><label class="sr-only" for="report-link">Private report link</label><input id="report-link" class="form-control mb-2" value="{{ $reportUrl }}" readonly onclick="this.select()"><a wire:navigate class="btn btn-sm btn-success" href="{{ $reportUrl }}" target="_blank" rel="noopener">Open client report</a></div>@endif
                    @if (in_array($order->status, ['new', 'quoted']) && $order->payment_status !== 'paid')<p class="text-muted">Save a quote and record verified payment to begin the audit.</p>@endif
                @else
                    <h3 class="h5">Homepage placement</h3><p class="small text-muted">All placements are labeled Sponsored. Multiple sponsors may share the homepage. Times below are UTC; placements automatically appear and expire based on these dates.</p>
                    @if ($order->payment_status === 'paid' && in_array($order->status, ['quoted', 'in_progress']))
                        @if ($order->starts_at)<p class="alert alert-info">{{ $order->ends_at->isPast() ? 'Expired — no longer displayed.' : ($order->starts_at->isFuture() ? 'Scheduled — starts in the future.' : 'Live on the homepage.') }}</p>@endif
                        <form wire:submit="activateSponsor" wire:confirm="Approve this sponsor copy and schedule it on the public homepage?">
                            @foreach (['sponsorName' => ['Business name', 100], 'sponsorTitle' => ['Headline', 120], 'sponsorDescription' => ['Description', 400], 'sponsorUrl' => ['Destination URL (HTTPS)', 2048]] as $field => [$label, $length])<label for="placement-{{ $field }}">{{ $label }}</label><input id="placement-{{ $field }}" class="form-control mb-2" wire:model="{{ $field }}" maxlength="{{ $length }}" type="{{ $field === 'sponsorUrl' ? 'url' : 'text' }}" required>@endforeach
                            <div class="row"><div class="col-sm-6"><label for="placement-start">Starts (UTC)</label><input id="placement-start" type="datetime-local" class="form-control" wire:model="startsAt" required></div><div class="col-sm-6"><label for="placement-end">Ends (UTC)</label><input id="placement-end" type="datetime-local" class="form-control" wire:model="endsAt" required></div></div><button class="btn btn-primary mt-3" wire:loading.attr="disabled">Approve and schedule placement</button>
                        </form>
                    @else<p class="text-muted">A verified payment is required before a placement can be scheduled.</p>@endif
                @endif
            </div>
        </div>
        <details class="mt-4"><summary>Activity history</summary><ul class="small mt-2">@foreach (array_reverse($order->history ?? []) as $event)<li>{{ $event['at'] }} — {{ $event['action'] }} · Admin #{{ $event['admin_id'] }} · {{ $event['currency'] }} {{ number_format(($event['amount_cents'] ?? 0) / 100, 2) }} · {{ $event['payment_status'] }}</li>@endforeach</ul></details>
    </section>
    @endif
</div>
