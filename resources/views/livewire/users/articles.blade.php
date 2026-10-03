<div class="w3-container w3-padding-32 articles-workspace">
    <header class="w3-panel w3-white w3-border w3-round-large w3-padding-24">
        <div class="w3-row">
            <div class="w3-col s12 m8">
                <p class="posts-eyebrow mb-2">Your workspace</p>
                <h1 class="h3 mb-2">My articles</h1>
                <p class="w3-text-grey mb-0">Write and manage your stories. Admin approval is required before
                    publication.</p>
            </div>
            <div class="w3-col s12 m4 w3-right-align w3-padding-16">
                <button type="button" class="w3-button w3-blue w3-round" wire:click="create">
                    <i class="fa-solid fa-plus mr-1" aria-hidden="true"></i>New article
                </button>
            </div>
        </div>
    </header>
    @if (session('articleStatus'))
    <div class="w3-panel w3-pale-green w3-leftbar w3-border-green w3-round" role="status">{{ session('articleStatus') }}
    </div>
    @endif
    @php
    $statusLabels = [
    'draft' => 'Awaiting approval',
    'published' => 'Published / scheduled',
    'archived' => 'Archived',
    ];
    @endphp
    <div class="w3-row-padding w3-margin-bottom" aria-label="Article statistics">
        @foreach ($statusLabels as $value => $label)
        <div class="w3-col s12 m4 w3-margin-bottom">
            <section class="w3-panel w3-white w3-border w3-round-large w3-padding-16">
                <strong class="h3 w3-block">{{ number_format($counts[$value] ?? 0) }}</strong>
                <span class="w3-text-grey">{{ $label }}</span>
            </section>
        </div>
        @endforeach
    </div>
    <div class="w3-row-padding w3-margin-bottom">
        <div class="w3-col s12 m6 w3-margin-bottom">
            <label for="article-search">Search by title</label>
            <input id="article-search" type="search" maxlength="200" class="w3-input w3-border w3-round"
                wire:model.live.debounce.300ms="search" placeholder="Search your articles...">
        </div>
        <div class="w3-col s12 m3 w3-margin-bottom">
            <label for="article-filter">Status</label>
            <select id="article-filter" class="w3-select w3-border w3-round" wire:model.live="filter">
                <option value="">All articles</option>
                <option value="draft">Awaiting approval</option>
                <option value="published">Published / scheduled</option>
                <option value="archived">Archived</option>
                <option value="trash">Trash ({{ $trashCount }})</option>
            </select>
        </div>
        <div class="w3-col s12 m3 w3-margin-bottom">
            <label for="article-sort">Sort by</label>
            <select id="article-sort" class="w3-select w3-border w3-round" wire:model.live="sort">
                <option value="updated">Recently updated</option>
                <option value="oldest">Oldest updated</option>
                <option value="title">Title A–Z</option>
            </select>
        </div>
    </div>
    <div class="w3-row w3-margin-bottom">
        <p class="w3-col s8 w3-small w3-text-grey" role="status">{{ number_format($posts->total()) }} articles found</p>
        @if ($search !== '' || $filter !== '' || $sort !== 'updated')
        <div class="w3-col s4 w3-right-align">
            <button type="button" class="w3-button w3-border w3-round w3-small" wire:click="clearFilters">Clear
                filters</button>
        </div>
        @endif
    </div>
    @forelse ($posts as $post)
    <article class="w3-panel w3-white w3-border w3-round-large w3-padding-16 w3-margin-bottom"
        wire:key="article-{{ $post->id }}">
        <div class="w3-row">
            <div class="w3-col s12 m8">
                <div class="d-flex align-items-center" style="gap: .5rem; min-width: 0;">
                    <h2 class="h5 mb-0" style="min-width: 0; overflow-wrap: anywhere;">{{ ucfirst($post->title) }}</h2>
                    @if ($post->remarks->isNotEmpty())
                    <details class="article-recommendations" wire:key="article-recommendations-{{ $post->id }}">
                        <summary class="article-recommendations-toggle"
                            aria-label="Show {{ $post->remarks->count() }} admin recommendations"
                            title="Show admin recommendations">
                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                            @if ($post->remarks->count() > 1)
                            <span class="article-recommendations-count">{{ $post->remarks->count() }}</span>
                            @endif
                        </summary>
                        <div class="article-recommendations-panel">
                            <h3 class="h6 font-weight-bold mb-2">Recommendations from admin</h3>
                            @foreach ($post->remarks as $remark)
                            <div class="article-recommendation-item" wire:key="author-remark-{{ $remark->id }}">
                                <p class="small text-muted mb-1">{{ $remark->admin?->name ?? 'Admin' }} &middot; {{
                                    $remark->created_at->format('M j, Y H:i') }}</p>
                                <p class="mb-1" style="white-space: pre-wrap; overflow-wrap: anywhere;">{{
                                    $remark->message
                                    }}</p>
                            </div>
                            @endforeach
                        </div>
                    </details>
                    @endif
                </div>
                <p class="w3-small w3-text-grey w3-margin-top">Updated {{ $post->updated_at?->format('M j, Y H:i') }}
                </p>
                @if ($post->excerpt)
                <p class="w3-text-dark-grey mb-0" style="overflow-wrap: anywhere;">{{ ucfirst($post->excerpt) }}</p>
                @endif
            </div>
            <div class="w3-col s12 m4 w3-right-align">
                <span
                    class="w3-tag w3-round w3-margin-bottom {{ $post->status === 'published' ? 'w3-pale-green w3-text-green' : ($post->status === 'draft' ? 'w3-pale-yellow w3-text-brown' : 'w3-light-grey w3-text-dark-grey') }}">{{
                    $post->trashed() ? 'In trash' : ($post->status === 'draft' ? 'Draft — awaiting approval' :
                    ($post->status === 'published' && $post->published_at?->isFuture() ? 'Scheduled' :
                    ucfirst($post->status))) }}</span>
                <div class="w3-bar">
                    @if ($post->trashed())
                    <button type="button" class="w3-button w3-border w3-round w3-small"
                        wire:click="restore({{ $post->id }})" wire:loading.attr="disabled">Restore as draft</button>
                    @else
                    <button type="button" class="w3-button w3-border w3-round w3-small w3-margin-right"
                        wire:click="edit({{ $post->id }})">Read / Edit</button>
                    @if ($post->status !== 'archived')<button type="button"
                        class="w3-button w3-border w3-round w3-small w3-margin-right"
                        wire:click="archive({{ $post->id }})"
                        wire:confirm="Archive this article and remove it from public view?"
                        wire:loading.attr="disabled">Archive</button>@endif
                    @if (auth()->user()->role !== 'user')
                    <button type="button" class="w3-button w3-border w3-round w3-small w3-text-red"
                        wire:click="delete({{ $post->id }})"
                        wire:confirm="Delete this article? It will be removed from the public list."
                        wire:loading.attr="disabled">Delete</button>
                    @endif
                    @endif
                </div>
            </div>
        </div>
    </article>
    @empty
    <div class="w3-panel w3-white w3-border w3-round-large w3-padding-32 w3-center">
        <i class="fa-regular fa-file-lines fa-2x w3-text-grey w3-margin-bottom" aria-hidden="true"></i>
        <p class="w3-large w3-text-dark-grey">{{ $filter === 'trash' ? 'Trash is empty for these filters.' : ($search
            !== '' || $filter !== '' ? 'No articles match your filters.' : 'No articles yet. Create your first article
            to get started.') }}</p>
    </div>
    @endforelse
    {{ $posts->links() }}

    @if ($showEditor)
    <div class="article-modal-backdrop" wire:key="article-editor" x-data x-init="$nextTick(() => $refs.title.focus())"
        @keydown.escape.window="$wire.cancel()">
        <section class="article-modal card w3-white" role="dialog" aria-modal="true" aria-labelledby="editor-heading"
            x-trap.inert.noscroll="true">
            <div class="card-header w3-container d-flex justify-content-between align-items-center">
                <h2 id="editor-heading" class="h5 mb-0">{{ $postId ? 'Edit article' : 'New article' }}</h2>
                <button type="button" class="w3-button w3-round btn btn-sm modal-close-button" aria-label="Close editor"
                    wire:click="cancel"><span aria-hidden="true">&times;</span></button>
            </div>
            <form wire:submit="save" class="card-body w3-container" novalidate>
                @error('conflict') <p class="w3-panel w3-pale-yellow w3-leftbar w3-border-yellow" role="alert">{{
                    $message }}</p> @enderror
                <label for="article-title">Title</label>
                <input id="article-title" x-ref="title" class="w3-input w3-border w3-round w3-margin-bottom"
                    wire:model="title" maxlength="255" required>
                @error('title') <p class="w3-text-red w3-small" role="alert">{{ $message }}</p> @enderror
                <label for="article-content">Article text</label>
                <textarea id="article-content" class="w3-input w3-border w3-round w3-margin-bottom" wire:model="content"
                    rows="10" maxlength="100000" required></textarea>
                @error('content') <p class="w3-text-red w3-small" role="alert">{{ $message }}</p> @enderror
                <label for="article-category">Category (optional)</label>
                <select id="article-category" class="w3-select w3-border w3-round w3-margin-bottom"
                    wire:model="category">
                    <option value="">No category</option>
                    @if ($category !== '' && !\App\Enums\PostCategory::tryFrom($category))
                    <option value="{{ $category }}" disabled>{{ $category }} (choose a new category)</option>
                    @endif
                    @foreach (\App\Enums\PostCategory::cases() as $option)
                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
                @error('category') <p class="w3-text-red w3-small" role="alert">{{ $message }}</p> @enderror
                <div class="row">
                    <div class="w3-col s12 m6 w3-padding-small"><label for="article-icon">Icon</label><select
                            id="article-icon" class="w3-select w3-border w3-round w3-margin-bottom" wire:model="icon">
                            <option value="">No icon</option>
                            @foreach (\App\Models\Post::ICONS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>@error('icon') <p class="w3-text-red w3-small" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="w3-col s12 m6 w3-padding-small">
                        <p class="w3-small w3-text-grey">Saving creates a draft for admin approval, including changes to
                            published articles.</p>
                    </div>
                </div>
                <div class="w3-right-align w3-margin-top">
                    <button type="button" class="w3-button w3-border w3-round w3-margin-right"
                        wire:click="cancel">Cancel</button>
                    <button type="submit" class="w3-button w3-blue w3-round" wire:loading.attr="disabled"
                        wire:target="save">Save article</button>
                </div>
            </form>
        </section>
    </div>
    @endif
</div>