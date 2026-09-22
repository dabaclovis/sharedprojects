<div class="admin-dashboard py-4">
    @if (session('quoteStatus'))<div class="alert alert-success" role="status">{{ session('quoteStatus') }}</div>@endif
    @if (session('approvalStatus')) <div class="alert alert-success" role="status">{{ session('approvalStatus') }}</div> @endif
    <header class="admin-welcome p-4 p-md-5 mb-4 d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <p class="small text-uppercase font-weight-bold mb-2">CD administration</p>
            <h1 class="h2 font-weight-bold">Admin dashboard</h1>
            <p class="mb-0">Welcome, {{ ucfirst(auth()->user()->name) }}. Your community and content at a glance.</p>
        </div>
        <a class="btn btn-light" href="{{ route('admins.users') }}"><i class="fa-solid fa-users-gear mr-2" aria-hidden="true"></i>Manage users</a>
    </header>

    <nav class="d-flex flex-wrap mb-4" style="gap: .5rem;" aria-label="Dashboard shortcuts">
        <a class="btn btn-outline-primary btn-sm" href="{{ route('admins.users') }}">Manage accounts</a>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('admins.articles') }}">My articles</a>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('admins.products') }}">My products</a>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('admins.calendar') }}">My calendar</a>
    </nav>

    <section class="row" aria-label="Application statistics">
        @foreach ([['users', 'Registered users', 'activeUsers', 'active accounts', 'fa-users', 'blue'], ['articles', 'Articles', 'liveArticles', 'public now', 'fa-newspaper', 'purple'], ['products', 'Affiliate products', 'liveProducts', 'public now', 'fa-bag-shopping', 'teal'], ['events', 'Calendar events', 'upcomingEvents', 'upcoming scheduled', 'fa-calendar-days', 'amber']] as [$total, $label, $detail, $caption, $icon, $color])
            <div class="col-sm-6 col-xl-3 mb-4">
                <div class="dashboard-panel p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h6 mb-0">{{ $label }}</h2><span class="profile-icon profile-icon-{{ $color }}"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i></span></div>
                    <p class="h2 font-weight-bold mb-1">{{ number_format($stats[$total]) }}</p>
                    <p class="small text-muted mb-0">{{ number_format($stats[$detail]) }} {{ $caption }}</p>
                </div>
            </div>
        @endforeach
    </section>

    <section class="dashboard-panel p-3 mb-4" aria-label="Activity summary">
        <div class="row text-center">
            <div class="col-sm-4 py-2"><strong>{{ number_format($stats['newUsers']) }}</strong><span class="text-muted"> new accounts in 30 days</span></div>
            <div class="col-sm-4 py-2"><strong>{{ number_format($stats['draftArticles'] + $stats['draftProducts']) }}</strong><span class="text-muted"> article and product drafts</span></div>
            <div class="col-sm-4 py-2"><strong>{{ number_format($stats['clicks']) }}</strong><span class="text-muted"> affiliate link visits</span></div>
        </div>
        <p class="small text-muted text-center mb-0">Totals exclude deleted records.</p>
    </section>

    <div class="row">
        <section class="col-xl-8 mb-4" aria-labelledby="content-heading">
            <div class="dashboard-panel p-3 p-md-4 h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <h2 id="content-heading" class="h5 mb-2">Content overview</h2>
                    <a class="small" href="{{ route('admins.pages.'.$activeSection) }}">View public {{ $activeSection }} &rarr;</a>
                </div>
                <div class="btn-group mb-3" role="group" aria-label="Content type">
                    @foreach (['articles' => 'Articles', 'products' => 'Products', 'quotes' => 'Quotes'] as $value => $label)
                        <button type="button" class="btn btn-sm {{ $activeSection === $value ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="$set('section', '{{ $value }}')" aria-pressed="{{ $activeSection === $value ? 'true' : 'false' }}">{{ $label }}</button>
                    @endforeach
                </div>
                <div class="row mb-3">
                    <div class="col-md-8 mb-2">
                        <label class="sr-only" for="admin-content-search">{{ $activeSection === 'quotes' ? 'Search quotes by title, content or author' : 'Search '.$activeSection.' by title' }}</label>
                        <div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span></div><input id="admin-content-search" class="form-control" type="search" maxlength="200" placeholder="{{ $activeSection === 'quotes' ? 'Search title, quote or author...' : 'Search '.$activeSection.' by title...' }}" wire:model.live.debounce.300ms="search"></div>
                    </div>
                    @if ($activeSection !== 'quotes')
                    <div class="col-md-4 mb-2"><label class="sr-only" for="admin-content-status">Filter by status</label><select id="admin-content-status" class="custom-select" wire:model.live="status"><option value="">All statuses</option><option value="draft">Draft</option><option value="published">Published / scheduled</option><option value="archived">Archived</option></select></div>
                    @endif
                </div>
                <p class="small text-muted" role="status" aria-live="polite">{{ number_format($records->total()) }} {{ $activeSection }} found</p>
                <div wire:loading.class="posts-loading" wire:target="search,status,section,gotoPage,nextPage,previousPage">
                    @forelse ($records as $record)
                        @if ($activeSection === 'quotes')
                            <article class="admin-content-row py-3" wire:key="admin-quotes-{{ $record->id }}">
                                <div class="d-flex flex-wrap justify-content-between" style="gap: .5rem;">
                                    <h3 class="h6 admin-content-title">{{ $record->title ?: 'Untitled quote' }}</h3>
                                    @if ($record->category)<span class="badge badge-light align-self-start">{{ $record->category }}</span>@endif
                                </div>
                                <blockquote class="mb-2 admin-content-title">
                                    <p class="mb-2">{{ \Illuminate\Support\Str::limit($record->content, 350) }}</p>
                                    <footer class="small text-muted">{{ $record->author ?: 'Unknown author' }}@if ($record->source) &middot; {{ $record->source }}@endif</footer>
                                </blockquote>
                                <p class="small text-muted mb-0">Updated {{ $record->updated_at?->format('M j, Y') ?? 'Unknown' }}@if ($record->language) &middot; {{ $record->language }}@endif</p>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" wire:click="editQuote({{ $record->id }})">Edit quote #{{ $record->id }}</button>
                            </article>
                            @continue
                        @endif
                        @php
                            $owner = $activeSection === 'products' ? $record->user : $record->author;
                            $isLive = $record->status === 'published' && ($activeSection === 'products' ? $owner?->status === 'active' : ($record->published_at && $record->published_at->lte(now())));
                            $state = $activeSection === 'articles' && $record->status === 'published' && $record->published_at?->isFuture() ? 'Scheduled' : ucfirst($record->status);
                        @endphp
                        <article class="admin-content-row py-3" wire:key="admin-{{ $activeSection }}-{{ $record->id }}">
                            <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap: .5rem;">
                                <h3 class="h6 mb-1 admin-content-title">{{ ucfirst($record->title) }}</h3>
                                <span class="badge {{ $isLive ? 'badge-success' : 'badge-light' }}">{{ $state }}</span>
                            </div>
                            <p class="small text-muted mb-2">{{ $owner?->name ?? 'Deleted account' }} &middot; Updated {{ $record->updated_at->format('M j, Y') }}@if ($activeSection === 'products') &middot; {{ number_format($record->clicks) }} link visits @endif</p>
                            @if ($activeSection === 'articles')
                                <button type="button" class="btn btn-outline-primary btn-sm mb-2" wire:click="reviewArticle({{ $record->id }})">Review / Manage</button>
                            @endif
                            @if ($activeSection === 'articles' && $isLive)
                                <a class="small mr-3" href="{{ route('admins.pages.postshow', $record->slug) }}">Read article &rarr;</a>
                            @endif
                            @if ($owner?->id === auth()->id())
                                <a class="small" href="{{ route($activeSection === 'products' ? 'admins.products' : 'admins.articles') }}">Manage my {{ $activeSection }}</a>
                            @endif
                            @if ($activeSection === 'products' && $record->status === 'published' && ! $isLive)
                                <span class="small text-muted">Hidden publicly: contributor account is inactive.</span>
                            @endif
                        </article>
                    @empty
                        <div class="text-center py-5"><i class="fa-solid fa-folder-open fa-2x text-muted mb-3" aria-hidden="true"></i><p>No {{ $activeSection }} match these filters.</p>@if ($search !== '' || $status !== '')<button class="btn btn-outline-primary btn-sm" wire:click="clearFilters">Clear filters</button>@endif</div>
                    @endforelse
                </div>
                <div class="mt-3">{{ $records->links(data: ['scrollTo' => false]) }}</div>
                @if ($activeSection !== 'quotes')
                    <a class="small" href="{{ route($activeSection === 'products' ? 'admins.products' : 'admins.articles') }}">Create and manage my {{ $activeSection }} &rarr;</a>
                @endif
            </div>
        </section>
        <aside class="col-xl-4">
            <section class="dashboard-panel p-4 mb-4" aria-labelledby="quotes-heading">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 id="quotes-heading" class="h5 mb-0">Quote library</h2>
                    <span class="profile-icon profile-icon-purple"><i class="fa-solid fa-quote-left" aria-hidden="true"></i></span>
                </div>
                <p class="h2 font-weight-bold mb-1">{{ number_format($stats['quotes']) }}</p>
                <p class="small text-muted">{{ number_format($stats['newQuotes']) }} added in the last 30 days</p>
                <button type="button" class="btn btn-outline-primary btn-sm" wire:click="$set('section', 'quotes')">Browse quotes</button>
            </section>
            <section class="dashboard-panel p-4 mb-4" aria-labelledby="recent-users-heading">
                <h2 id="recent-users-heading" class="h5 mb-3">Recent registrations</h2>
                @foreach ($recentUsers as $member)
                    <div class="admin-content-row py-3" wire:key="recent-user-{{ $member->id }}"><div class="d-flex justify-content-between align-items-center" style="gap: .5rem;"><strong class="admin-content-title">{{ ucfirst($member->name) }}</strong><span class="badge {{ $member->status === 'active' ? 'badge-success' : 'badge-secondary' }}">{{ ucfirst($member->status) }}</span></div><p class="small text-muted mb-1 admin-content-title">{{ $member->email }}</p><span class="small text-muted">{{ ucfirst($member->role) }} &middot; {{ $member->created_at?->format('M j, Y') }}</span></div>
                @endforeach
                <a class="d-inline-block small mt-3" href="{{ route('admins.users') }}">Manage all accounts &rarr;</a>
            </section>
            <section class="dashboard-panel p-4 mb-4" aria-labelledby="scheduled-events-heading">
                <h2 id="scheduled-events-heading" class="h5">All scheduled events</h2><p class="small text-muted">Schedules from all users. Times use each event's time zone.</p>
                @forelse ($scheduledEvents as $event)
                    <div class="admin-content-row py-3" wire:key="scheduled-event-{{ $event->id }}"><h3 class="h6 admin-content-title">{{ ucfirst($event->title) }}</h3><p class="small text-muted mb-1">{{ $event->user?->name ?? 'Deleted account' }}</p><p class="small text-muted mb-0">{{ $event->starts_at->setTimezone($event->timezone)->format('M j, Y H:i') }}<br>{{ $event->timezone }}</p></div>
                @empty
                    <p class="small text-muted py-3">No scheduled events.</p>
                @endforelse
                <div class="mt-3">{{ $scheduledEvents->links(data: ['scrollTo' => false]) }}</div>
                <a class="small" href="{{ route('admins.calendar') }}">Open my calendar &rarr;</a>
            </section>
        </aside>
    </div>
    @if ($editingQuoteId)
        <div class="article-modal-backdrop" wire:key="edit-quote-{{ $editingQuoteId }}" x-data x-init="$nextTick(() => $refs.quoteContent.focus())" @keydown.escape.window="$wire.closeQuoteEditor()">
            <section class="article-modal card" role="dialog" aria-modal="true" aria-labelledby="edit-quote-heading" x-trap.inert.noscroll="true">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 id="edit-quote-heading" class="h5 mb-0">Edit quote #{{ $editingQuoteId }}</h2>
                    <button type="button" class="close" wire:click="closeQuoteEditor" aria-label="Close quote editor">&times;</button>
                </div>
                <form class="card-body" wire:submit="saveQuote">
                    <label for="admin-quote-content">Quote</label>
                    <textarea id="admin-quote-content" class="form-control mb-2" rows="4" maxlength="250" wire:model="quoteFields.content" x-ref="quoteContent" required></textarea>
                    @error('quoteFields.content')<p class="text-danger small" role="alert">{{ $message }}</p>@enderror
                    @foreach (['title' => 'Title', 'author' => 'Author', 'source' => 'Source', 'tags' => 'Tags', 'language' => 'Language'] as $field => $label)
                        <label for="admin-quote-{{ $field }}">{{ $label }} (optional)</label>
                        <input id="admin-quote-{{ $field }}" class="form-control mb-2" wire:model="quoteFields.{{ $field }}" maxlength="255">
                        @error('quoteFields.'.$field)<p class="text-danger small" role="alert">{{ $message }}</p>@enderror
                    @endforeach
                    <label for="admin-quote-category">Category</label>
                    <select id="admin-quote-category" class="custom-select mb-2" wire:model="quoteFields.category"><option value="">No category</option>@foreach (\App\Enums\QuoteCategory::cases() as $category)<option value="{{ $category->value }}">{{ $category->label() }}</option>@endforeach</select>
                    @error('quoteFields.category')<p class="text-danger small" role="alert">{{ $message }}</p>@enderror
                    <div class="row">
                        @foreach (['licon' => 'Left icon', 'ricon' => 'Right icon'] as $field => $label)
                            <div class="col-sm-6"><label for="admin-quote-{{ $field }}">{{ $label }}</label><select id="admin-quote-{{ $field }}" class="custom-select mb-2" wire:model="quoteFields.{{ $field }}">@foreach (\App\Models\Quote::ICONS as $value => $name)<option value="{{ $value }}">{{ $name }}</option>@endforeach</select>@error('quoteFields.'.$field)<p class="text-danger small" role="alert">{{ $message }}</p>@enderror</div>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-end mt-3"><button type="button" class="btn btn-light mr-2" wire:click="closeQuoteEditor">Cancel</button><button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Save quote</button></div>
                </form>
            </section>
        </div>
    @endif
    @if ($reviewPost)
        <div class="article-modal-backdrop" wire:key="review-{{ $reviewPost->id }}" x-data x-init="$nextTick(() => $refs.closeReview.focus())" @keydown.escape.window="$wire.closeReview()">
            <section class="article-modal card" role="dialog" aria-modal="true" aria-labelledby="review-heading" x-trap.inert.noscroll="true">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 id="review-heading" class="h5 mb-0">{{ $reviewPost->title }}</h2>
                    <button type="button" class="close" x-ref="closeReview" wire:click="closeReview" aria-label="Close review">&times;</button>
                </div>
                <div class="card-body">
                    <p class="small text-muted">{{ $reviewPost->author?->name ?? 'Deleted account' }} &middot; {{ ucfirst($reviewPost->status) }}</p>
                    @if ($reviewPost->excerpt)<p>{{ $reviewPost->excerpt }}</p>@endif
                    <div style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $reviewPost->content }}</div>
                    <hr>
                    <h3 class="h6">Remarks to the author</h3>
                    @foreach ($reviewPost->remarks as $remark)
                        <div class="border-bottom py-2" wire:key="admin-remark-{{ $remark->id }}">
                            <p class="small text-muted mb-1">{{ $remark->admin?->name ?? 'Admin' }} &middot; {{ $remark->created_at->format('M j, Y H:i') }}</p>
                            <p style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $remark->message }}</p>
                        </div>
                    @endforeach
                    @if (session('remarkStatus'))<p class="text-success" role="status">{{ session('remarkStatus') }}</p>@endif
                    <form wire:submit="sendRemark" class="mt-3">
                        <label for="remark-message">Message to the author</label>
                        <textarea id="remark-message" class="form-control" rows="3" wire:model="remarkMessage" maxlength="5000" required placeholder="Explain any unacceptable language and what the author should change."></textarea>
                        @error('remarkMessage')<p class="text-danger small" role="alert">{{ $message }}</p>@enderror
                        <p class="small text-muted mt-2">Only admins and this post's author can see these remarks. Use Archive separately to remove a published post from public view.</p>
                        <button type="submit" class="btn btn-primary btn-sm mt-2" wire:loading.attr="disabled">Send message</button>
                    </form>
                </div>
                <div class="card-footer d-flex justify-content-end" style="gap: .5rem;">
                    <button type="button" class="btn btn-light" wire:click="closeReview">Close</button>
                    @if ($reviewPost->status !== 'archived')
                        <button type="button" class="btn btn-outline-secondary" wire:click="archiveReviewedArticle" wire:loading.attr="disabled">Archive</button>
                    @endif
                    @if ($reviewPost->status !== 'published' || ! $reviewPost->published_at || $reviewPost->published_at->isFuture())
                        <button type="button" class="btn btn-success" wire:click="publishReviewedArticle" wire:loading.attr="disabled">Publish</button>
                    @endif
                </div>
            </section>
        </div>
    @endif
</div>
