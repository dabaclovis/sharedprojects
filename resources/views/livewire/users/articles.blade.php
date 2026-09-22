<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h3">My articles</h1><p class="text-muted mb-0">Write and manage your stories. Admin approval is required before publication.</p></div>
        <button class="btn btn-primary" wire:click="create">New article</button>
    </div>
    @if (session('articleStatus')) <div class="alert alert-success" role="status">{{ session('articleStatus') }}</div> @endif
    <label class="sr-only" for="article-search">Search my articles</label>
    <input id="article-search" type="search" class="form-control mb-4" wire:model.live.debounce.300ms="search" placeholder="Search your articles...">
    @forelse ($posts as $post)
        <article class="card mb-3 w3-round-xlarge" wire:key="article-{{ $post->id }}">
            <div class="card-body py-2 px-3 d-flex flex-wrap align-items-center" style="gap: 1rem;">
                <h2 class="h5 mb-0 flex-grow-1" style="min-width: 0; overflow-wrap: anywhere;">{{ ucfirst($post->title) }}</h2>
                <span class="badge badge-light">{{ $post->status === 'draft' ? 'Draft ? awaiting approval' : ucfirst($post->status) }}</span>
                <div class="d-flex flex-shrink-0 ml-auto">
                    <button class="btn btn-outline-primary btn-sm mr-2" wire:click="edit({{ $post->id }})">Read / Edit</button>
                    <button class="btn btn-outline-danger btn-sm" wire:click="delete({{ $post->id }})" wire:confirm="Delete this article? It will be removed from the public list." wire:loading.attr="disabled">Delete</button>
                </div>
            </div>
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
        <p class="text-center text-muted py-5">No articles found. Create your first article to get started.</p>
    @endforelse
    {{ $posts->links() }}

    @if ($showEditor)
        <div class="article-modal-backdrop" wire:key="article-editor" x-data x-init="$nextTick(() => $refs.title.focus())" @keydown.escape.window="$wire.cancel()">
            <section class="article-modal card" role="dialog" aria-modal="true" aria-labelledby="editor-heading" x-trap.inert.noscroll="true">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 id="editor-heading" class="h5 mb-0">{{ $postId ? 'Edit article' : 'New article' }}</h2>
                    <button type="button" class="close" aria-label="Close editor" wire:click="cancel">&times;</button>
                </div>
                <form wire:submit="save" class="card-body" novalidate>
                    <label for="article-title">Title</label>
                    <input id="article-title" x-ref="title" class="form-control mb-2" wire:model="title" maxlength="255" required>
                    @error('title') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                    <label for="article-content">Article text</label>
                    <textarea id="article-content" class="form-control mb-2" wire:model="content" rows="10" required></textarea>
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
