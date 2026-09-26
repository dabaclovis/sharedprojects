<div class="py-4">
    <header class="admin-welcome p-4 mb-4"><h1 class="h3">Visitor ratings</h1><p class="mb-0">Feedback from guests and registered members.</p></header>
    <section class="dashboard-panel p-4 mb-4"><strong>{{ $summary->total ? number_format($summary->average, 1).' / 5' : 'No ratings yet' }}</strong><span class="ml-3 text-muted">{{ $summary->total }} ratings</span></section>
    <section class="dashboard-panel p-4">
        @forelse ($ratings as $rating)
            <article class="border-bottom py-3" wire:key="rating-{{ $rating->id }}">
                <div class="d-flex justify-content-between flex-wrap"><h2 class="h6">{{ $rating->user?->name ?? 'Guest' }} &middot; {{ $rating->score }} / 5</h2><small class="text-muted">{{ $rating->created_at->format('M j, Y H:i') }} UTC</small></div>
                <p class="small text-muted">IP address: {{ $rating->ip_address ?? 'Not recorded' }}</p>
                <p class="mb-0" style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $rating->feedback ?: 'No written feedback.' }}</p>
            </article>
        @empty<p class="text-muted">Ratings will appear here after visitors submit them.</p>@endforelse
        <div class="mt-3">{{ $ratings->links() }}</div>
    </section>
</div>
