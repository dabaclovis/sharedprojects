<div class="py-4">
    <header class="admin-welcome p-4 mb-4"><p class="small text-uppercase font-weight-bold">Administration</p><h1 class="h3">Manage {{ $kind }}</h1><p class="mb-0">Review and manage {{ $kind }} across all accounts. Personal creation and editing are available in My workspace.</p></header>
    @if (session('managementStatus'))<div class="alert alert-success" role="status">{{ session('managementStatus') }}</div>@endif
    <section class="dashboard-panel p-4">
        <div class="row"><div class="col-md-8 mb-3"><label for="management-search">Search title or owner</label><input id="management-search" type="search" maxlength="200" class="form-control" wire:model.live.debounce.300ms="search"></div><div class="col-md-4 mb-3"><label for="management-status">Status</label><select id="management-status" class="custom-select" wire:model.live="status"><option value="">All active records</option>@foreach ($statuses as $value)<option value="{{ $value }}">{{ ucfirst($value) }}</option>@endforeach<option value="trash">Trash</option></select></div></div>
        <div class="d-flex justify-content-between mb-3"><p class="small text-muted" role="status">{{ $records->total() }} {{ $kind }} found</p>@if ($search !== '' || $status !== '')<button class="btn btn-link btn-sm" wire:click="clearFilters">Clear filters</button>@endif</div>
        <div class="table-responsive"><table class="table"><thead><tr><th scope="col">Title</th><th scope="col">Owner</th><th scope="col">Status</th><th scope="col">{{ $kind === 'events' ? 'Starts' : 'Updated' }}</th><th scope="col">Action</th></tr></thead><tbody>
        @forelse ($records as $record)
            <tr wire:key="managed-{{ $kind }}-{{ $record->id }}"><td style="overflow-wrap: anywhere;">{{ $record->title }}</td><td>{{ $record->{$ownerRelation}?->name ?? 'Deleted account' }}</td><td>{{ $record->trashed() ? 'In trash' : ucfirst($record->status) }}</td><td>@if ($kind === 'events'){{ $record->starts_at->setTimezone($record->timezone)->format('M j, Y H:i') }}<br><small>{{ $record->timezone }}</small>@else{{ $record->updated_at?->format('M j, Y') }}@endif</td><td><button class="btn btn-outline-primary btn-sm" wire:click="review({{ $record->id }})" wire:loading.attr="disabled">Review</button></td></tr>
        @empty<tr><td colspan="5" class="text-center py-4 text-muted">No records match these filters.</td></tr>@endforelse
        </tbody></table></div>{{ $records->links(data: ['scrollTo' => false]) }}
    </section>
    @if ($selected)
        <div class="article-modal-backdrop" x-data x-init="$nextTick(() => $refs.close.focus())" @keydown.escape.window="$wire.close()" wire:key="management-review-{{ $selected->id }}">
            <section class="article-modal card" role="dialog" aria-modal="true" aria-labelledby="management-heading" x-trap.inert.noscroll="true">
                <div class="card-header d-flex justify-content-between"><h2 id="management-heading" class="h5">{{ $selected->title }}</h2><button type="button" class="w3-button w3-round btn btn-sm modal-close-button" x-ref="close" wire:click="close" aria-label="Close review"><span aria-hidden="true">&times;</span></button></div>
                <div class="card-body">
                    @error('review')<p class="alert alert-warning" role="alert">{{ $message }}</p>@enderror
                    <p class="small text-muted">{{ $selected->{$ownerRelation}?->name ?? 'Deleted account' }} &middot; {{ $selected->trashed() ? 'In trash' : ucfirst($selected->status) }}</p>
                    <p style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $kind === 'articles' ? $selected->content : $selected->description }}</p>
                    @if ($kind === 'events')<p>{{ $selected->starts_at->setTimezone($selected->timezone)->format('M j, Y H:i') }} &ndash; {{ $selected->ends_at->setTimezone($selected->timezone)->format('M j, Y H:i') }} ({{ $selected->timezone }})</p><p>{{ $selected->location }}</p>@endif
                    @if ($kind === 'products')<p><strong>Merchant:</strong> {{ $selected->merchant }}</p><p style="overflow-wrap: anywhere;"><strong>Destination:</strong> {{ $selected->affiliate_url }}</p><p>{{ $selected->currency }} {{ $selected->price }}</p>@if ($selected->user?->status !== 'active')<p class="alert alert-warning">This product stays hidden publicly while its owner is inactive.</p>@endif @endif
                    @if ($kind === 'articles' && ! $selected->trashed())
                        <h3 class="h6">Private remarks for the author</h3>
                        @foreach ($selected->remarks as $message)<div class="border-top py-2"><small>{{ $message->admin?->name ?? 'Admin' }}</small><p style="white-space: pre-wrap;">{{ $message->message }}</p></div>@endforeach
                        <form wire:submit="sendRemark"><label for="management-remark">Remark</label><textarea id="management-remark" class="form-control" wire:model="remark" rows="3" maxlength="5000" required></textarea>@error('remark')<p class="text-danger">{{ $message }}</p>@enderror<button class="btn btn-outline-primary btn-sm mt-2" wire:loading.attr="disabled">Save remark</button></form>
                    @endif
                </div>
                <div class="card-footer d-flex flex-wrap" style="gap: .5rem;">
                    @if ($selected->trashed())<button class="btn btn-primary" wire:click="restore" wire:confirm="Restore this record for review? It will not be published or scheduled automatically." wire:loading.attr="disabled">Restore</button>
                    @else
                        @if ($kind !== 'events' && ($selected->status !== 'published' || ($kind === 'articles' && $selected->published_at?->isFuture())))<button class="btn btn-success" wire:click="publish" wire:confirm="Approve and publish this content now?" wire:loading.attr="disabled">Approve and publish</button>@endif
                        @if (! in_array($selected->status, ['archived', 'cancelled']))<button class="btn btn-outline-secondary" wire:click="archive" wire:confirm="{{ $kind === 'events' ? 'Cancel this event?' : 'Archive this content and remove it from public view?' }}" wire:loading.attr="disabled">{{ $kind === 'events' ? 'Cancel event' : 'Archive' }}</button>@endif
                        <button class="btn btn-outline-danger" wire:click="trash" wire:confirm="Move this record to trash?" wire:loading.attr="disabled">Move to trash</button>
                    @endif
                    <button class="btn btn-light ml-auto" wire:click="close">Close</button>
                </div>
            </section>
        </div>
    @endif
</div>
