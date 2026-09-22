<div class="posts-page">
    <section class="jumbotron jumbotron-fluid text-center posts-hero mb-0" aria-labelledby="posts-heading">
        <div class="container py-4 py-md-5">
            <p class="posts-eyebrow mb-3">A space for curious minds</p>
            <h1 id="posts-heading" class="display-4 font-weight-bold">Good stories. Fresh perspectives.</h1>
            <p class="lead mx-auto mt-3 mb-4 posts-intro">Explore ideas, discover something new, and catch up on the latest from our community.</p>

            <div class="mx-auto posts-search">
                <label for="post-search" class="sr-only">Search posts by title, topic, or author</label>
                <div class="input-group input-group-lg shadow-sm">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white border-0 text-muted"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span>
                    </div>
                    <input id="post-search" type="search" class="form-control border-0" placeholder="Search stories, topics, or authors..." wire:model.live.debounce.300ms="search" autocomplete="off">
                </div>
                <p class="small mt-3 mb-0 posts-search-hint">Your next good read starts here.</p>
            </div>
        </div>
    </section>

    <section id="posts-list" class="container py-5" aria-label="Posts">
        <div class="d-flex flex-wrap justify-content-end align-items-center mb-4">
            <p class="text-muted small mt-3 mb-0" role="status" aria-live="polite">{{ $posts->total() }} {{ $posts->total() === 1 ? 'post' : 'posts' }}{{ trim($search) !== '' ? ' found' : ' to explore' }}</p>
        </div>

        <div class="row" wire:loading.class="posts-loading" wire:target="search, nextPage, previousPage, gotoPage">
            @forelse ($posts as $post)
                <div class="col-12 mb-4" wire:key="post-{{ $post['id'] }}">
                    <article class="card flex-row h-100 posts-card border-0">
                        @if (!empty($post['icon']))
                            <div class="posts-art posts-art-{{ $post['theme'] ?? 'community' }}" aria-hidden="true">
                                <i class="fa-solid {{ $post['icon'] }}"></i>
                            </div>
                        @endif
                        <div class="card-body p-4 d-flex flex-column">
                            <span class="posts-category mb-3">{{ $post['category'] }}</span>
                            <h3 class="h5 font-weight-bold posts-title">@guest <a href="{{ route('pages.postshow', $post->slug) }}">{{ ucfirst($post->title) }}</a> @else {{ ucfirst($post->title) }} @endguest</h3>
                            <p class="text-muted mt-2 mb-4">{{ ucfirst($post->excerpt ?: \Illuminate\Support\Str::limit($post->content, 180)) }}</p>
                            @guest <a class="mb-3" href="{{ route('pages.postshow', $post->slug) }}">Read article <i class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i></a> @endguest
                            <div class="mt-auto pt-3 posts-byline">
                                <p class="small text-muted mb-0"><time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('M j, Y') }}</time></p>
                            </div>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center bg-white rounded p-5 border">
                        <i class="fa-solid fa-magnifying-glass fa-2x text-muted mb-3" aria-hidden="true"></i>
                        <h3 class="h5">No posts found</h3>
                        <p class="text-muted">Try a different title, topic, or author.</p>
                        <button type="button" class="btn btn-outline-primary" wire:click="$set('search', '')">Clear search</button>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($posts->hasPages())
            <div class="mt-3">{{ $posts->links(data: ['scrollTo' => '#posts-list']) }}</div>
        @elseif ($posts->total() > 0)
            <nav class="mt-3" aria-label="Post pagination">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">Previous</span></li>
                    <li class="page-item active" aria-current="page"><span class="page-link">1<span class="sr-only"> (current)</span></span></li>
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">Next</span></li>
                </ul>
            </nav>
        @endif
    </section>
</div>
