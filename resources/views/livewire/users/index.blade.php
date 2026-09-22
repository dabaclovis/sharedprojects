<div class="user-dashboard py-5">
    <div class="container">
        <header class="dashboard-welcome p-4 p-md-5 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div class="mb-3 mb-md-0">
                    <p class="posts-eyebrow mb-2">Your workspace</p>
                    <h1 class="h2 font-weight-bold">Welcome back, {{ $user->name }}.</h1>
                    <p class="mb-0 text-muted">Keep track of your stories and see what is ready to share.</p>
                    <div class="mt-3 d-flex flex-wrap" style="gap: .5rem;"><a class="btn btn-outline-primary btn-sm" href="{{ route('services.affiliates') }}">Affiliate products</a><a class="btn btn-outline-primary btn-sm" href="{{ route('services.calendar') }}">My calendar</a></div>
                </div>
            </div>
        </header>

        <div class="row mb-2">
            @foreach ($stats as $label => $count)
            <div class="col-6 col-lg-3 mb-3">
                <div class="dashboard-panel p-4 h-100">
                    <p class="small text-muted mb-2">{{ $label }}</p>
                    <p class="h2 font-weight-bold mb-0">{{ number_format($count) }}</p>
                </div>
            </div>
            @endforeach
        </div>
        <div class="row">
            <section id="dashboard-posts" class="col-lg-8 mb-4" aria-labelledby="your-posts-heading">
                <div class="dashboard-panel p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 id="your-posts-heading" class="h5 font-weight-bold mb-0">Your posts</h2>
                        <a href="{{ route('users.articles') }}" class="btn btn-outline-primary btn-sm">Manage articles</a>
                        <span class="small text-muted" role="status">{{ $posts->total() }} results</span>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-8 mb-3">
                            <label for="dashboard-search" class="sr-only">Search your posts</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text bg-white"><i
                                            class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span></div>
                                <input id="dashboard-search" type="search" class="form-control"
                                    placeholder="Search your post titles..." wire:model.live.debounce.300ms="search">
                            </div>
                        </div>
                        <div class="col-sm-4 mb-3">
                            <label for="dashboard-status" class="sr-only">Filter by status</label>
                            <select id="dashboard-status" class="custom-select" wire:model.live="status">
                                <option value="">All statuses</option>
                                <option value="draft">Drafts</option>
                                <option value="published">Published / scheduled</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                    </div>
                    <div wire:loading.class="posts-loading"
                        wire:target="search,status,nextPage,previousPage,gotoPage,clearFilters">
                        @forelse ($posts as $post)
                        <article class="dashboard-post py-3" wire:key="dashboard-post-{{ $post->id }}">
                            <div class="d-flex flex-wrap justify-content-between align-items-start">
                                <h3 class="h6 font-weight-bold mr-3">{{ ucfirst($post->title) }}</h3>
                                <span
                                    class="badge badge-{{ $post->status === 'published' ? 'success' : ($post->status === 'draft' ? 'warning' : 'secondary') }}">
                                    {{ $post->status === 'published' && $post->published_at?->isFuture() ? 'Scheduled' :
                                    ($post->status === 'published' && !$post->published_at ? 'Awaiting publication date'
                                    : ucfirst($post->status)) }}
                                </span>
                            </div>
                            @if ($post->excerpt)
                            <p class="small text-muted mb-2">{{ ucfirst(\Illuminate\Support\Str::limit($post->excerpt, 160)) }}
                            </p>
                            @endif
                            <p class="small text-muted mb-0">{{ $post->category ?: 'Uncategorized' }} &middot; Updated
                                {{ $post->updated_at->diffForHumans() }}</p>
                        </article>
                        @empty
                        <div class="text-center py-5">
                            <i class="fa-regular fa-file-lines fa-2x text-muted mb-3" aria-hidden="true"></i>
                            <h3 class="h6">{{ $search !== '' || $status !== '' ? 'No matching posts' : 'Your stories
                                start here' }}</h3>
                            <p class="text-muted small">{{ $search !== '' || $status !== '' ? 'Try another title or
                                change the status filter.' : 'Your posts will appear here once you create them.' }}</p>
                            @if ($search !== '' || $status !== '')
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="clearFilters">Clear
                                filters</button>
                            @else
                            @guest <a href="{{ route('pages.articles') }}" class="btn btn-outline-primary btn-sm">Explore
                                community posts</a> @endguest
                            @endif
                        </div>
                        @endforelse
                    </div>
                    @if ($posts->hasPages())
                    <div class="mt-4">{{ $posts->links(data: ['scrollTo' => '#dashboard-posts']) }}</div>
                    @endif
                </div>
            </section>

            <aside class="col-lg-4" aria-label="Community">
                <div class="dashboard-panel p-4">
                    <h2 class="h6 font-weight-bold">Find your next idea</h2>
                    <p class="small text-muted">Discover stories and fresh perspectives from the community.</p>
                    @guest <a href="{{ route('pages.articles') }}">Browse posts <i class="fa-solid fa-arrow-right ml-1"
                            aria-hidden="true"></i></a> @endguest
                </div>
            </aside>
        </div>
    </div>
</div>
