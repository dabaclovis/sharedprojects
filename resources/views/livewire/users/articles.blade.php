<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h3">My articles</h1><p class="text-muted mb-0">Write and manage your stories. Admin approval is required before publication.</p></div>
        <button class="btn btn-primary" wire:click="create">New article</button>
    </div>
    @if (session('articleStatus')) <div class="alert alert-success" role="status">{{ session('articleStatus') }}</div> @endif
    <div class="row mb-3" aria-label="Article statistics">
        @foreach (['draft' => 'Awaiting approval', 'published' => 'Published / scheduled', 'archived' => 'Archived'] as $value => $label)
            <div class="col-sm-4 mb-2"><div class="card card-body"><strong class="h3">{{ number_format($counts[$value] ?? 0) }}</strong><span class="text-muted">{{ $label }}</span></div></div>
        @endforeach
    </div>
    <div class="row mb-3">
        <div class="col-md-6 mb-2"><label for="article-search">Search by title</label><input id="article-search" type="search" maxlength="200" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Search your articles..."></div>
        <div class="col-md-3 mb-2"><label for="article-filter">Status</label><select id="article-filter" class="custom-select" wire:model.live="filter"><option value="">All articles</option><option value="draft">Awaiting approval</option><option value="published">Published / scheduled</option><option value="archived">Archived</option><option value="trash">Trash ({{ $trashCount }})</option></select></div>
        <div class="col-md-3 mb-2"><label for="article-sort">Sort by</label><select id="article-sort" class="custom-select" wire:model.live="sort"><option value="updated">Recently updated</option><option value="oldest">Oldest updated</option><option value="title">Title A–Z</option></select></div>
    </div>
    <div class="d-flex justify-content-between mb-3"><p class="small text-muted mb-0" role="status">{{ number_format($posts->total()) }} articles found</p>@if ($search !== '' || $filter !== '' || $sort !== 'updated')<button class="btn btn-link btn-sm" wire:click="clearFilters">Clear filters</button>@endif</div>
    @forelse ($posts as $post)
        <article class="card mb-3 w3-round-xlarge" wire:key="article-{{ $post->id }}">
            <div class="card-body py-2 px-3 d-flex flex-wrap align-items-center" style="gap: 1rem;">
                <h2 class="h5 mb-0 flex-grow-1" style="min-width: 0; overflow-wrap: anywhere;">{{ ucfirst($post->title) }}</h2>
                <span class="badge badge-light">{{ $post->trashed() ? 'In trash' : ($post->status === 'draft' ? 'Draft — awaiting approval' : ($post->status === 'published' && $post->published_at?->isFuture() ? 'Scheduled' : ucfirst($post->status))) }}</span>
                <div class="d-flex flex-shrink-0 ml-auto">
                    @if ($post->trashed())
                    <button class="btn btn-outline-primary btn-sm" wire:click="restore({{ $post->id }})" wire:loading.attr="disabled">Restore as draft</button>
                    @else
                    <button class="btn btn-outline-primary btn-sm mr-2" wire:click="edit({{ $post->id }})">Read / Edit</button>
                    @if ($post->status !== 'archived')<button class="btn btn-outline-secondary btn-sm mr-2" wire:click="archive({{ $post->id }})" wire:confirm="Archive this article and remove it from public view?" wire:loading.attr="disabled">Archive</button>@endif
                    <button class="btn btn-outline-danger btn-sm" wire:click="delete({{ $post->id }})" wire:confirm="Delete this article? It will be removed from the public list." wire:loading.attr="disabled">Delete</button>
                    @endif
                </div>
            </div>
            <div class="card-body pt-0"><p class="small text-muted mb-1">Updated {{ $post->updated_at?->format('M j, Y H:i') }}</p>@if ($post->excerpt)<p class="mb-0" style="overflow-wrap: anywhere;">{{ $post->excerpt }}</p>@endif</div>
            @if ($post->remarks->isNotEmpty())
                <section class="card-body border-top" aria-label="Messages from admin">
                    <h3 class="h6">Messages from admin</h3>
                    @foreach ($post->remarks as $remark)
                        <div class="border-bottom py-2" wire:key="author-remark-{{ $remark->id }}">
                            <p class="small text-muted mb-1">{{ $remark->admin?->name ?? 'Admin' }} &middot; {{ $remark->created_at->format('M j, Y H:i') }}</p>
                            <p class="mb-1" style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $remark->message }}</p>
                        </div>
                    @endforeach
                </section>
            @endif
        </article>
    @empty
        <p class="text-center text-muted py-5">{{ $filter === 'trash' ? 'Trash is empty for these filters.' : ($search !== '' || $filter !== '' ? 'No articles match your filters.' : 'No articles yet. Create your first article to get started.') }}</p>
    @endforelse
    {{ $posts->links() }}

    @if ($showEditor)
        <div class="article-modal-backdrop" wire:key="article-editor" x-data x-init="$nextTick(() => $refs.title.focus())" @keydown.escape.window="$wire.cancel()">
            <section class="article-modal card" role="dialog" aria-modal="true" aria-labelledby="editor-heading" x-trap.inert.noscroll="true">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 id="editor-heading" class="h5 mb-0">{{ $postId ? 'Edit article' : 'New article' }}</h2>
                    <button type="button" class="w3-button w3-round btn btn-sm modal-close-button" aria-label="Close editor" wire:click="cancel"><span aria-hidden="true">&times;</span></button>
                </div>
                <form wire:submit="save" class="card-body" novalidate>
                    @error('conflict') <p class="alert alert-warning" role="alert">{{ $message }}</p> @enderror
                    <label for="article-title">Title</label>
                    <input id="article-title" x-ref="title" class="form-control mb-2" wire:model="title" maxlength="255" required>
                    @error('title') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                    <label for="article-content">Article text</label>
                    <textarea id="article-content" class="form-control mb-2" wire:model="content" rows="10" maxlength="100000" required></textarea>
                    @error('content') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                    <label for="article-category">Category (optional)</label>
                    <select id="article-category" class="custom-select mb-2" wire:model="category">
                        <option value="">No category</option>
                        @if ($category !== '' && !\App\Enums\PostCategory::tryFrom($category))
                            <option value="{{ $category }}" disabled>{{ $category }} (choose a new category)</option>
                        @endif
                        @foreach (\App\Enums\PostCategory::cases() as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </select>
                    @error('category') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                    <div class="row">
                        <div class="col-sm-6"><label for="article-icon">Icon</label><select id="article-icon" class="custom-select mb-2" wire:model="icon"><option value="">No icon</option><option value="fa-file-lines">Article</option><option value="fa-lightbulb">Idea</option><option value="fa-comments">Conversation</option><option value="fa-seedling">Growth</option></select>@error('icon') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror</div>
                        <div class="col-sm-6"><p class="small text-muted">Saving creates a draft for admin approval, including changes to published articles.</p></div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-light mr-2" wire:click="cancel">Cancel</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">Save article</button>
                    </div>
                </form>
            </section>
        </div>
    @endif
</div>
