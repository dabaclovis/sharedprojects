<div class="posts-page py-5">
    <div class="container">
        <div class="row">
        <div class="col-12">
            @guest <a class="d-inline-block mb-4" href="{{ route('pages.articles') }}"><i class="fa-solid fa-arrow-left mr-2" aria-hidden="true"></i>Back to articles</a> @endguest
            <article class="card posts-card border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap: .75rem;">
                        <h1 class="h2 font-weight-bold mb-0" style="min-width: 0; overflow-wrap: anywhere; flex: 1 1 240px;">{{ ucfirst($post->title) }}</h1>
                        @if ($post->category)
                            <span class="posts-category">{{ ucfirst($post->category) }}</span>
                        @endif
                    </div>
                    <p class="text-muted small mt-3 mb-4">
                        <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->format('M j, Y') }}</time>
                    </p>
                    @if ($post->excerpt)
                        <p class="lead text-muted">{{ ucfirst($post->excerpt) }}</p>
                    @endif
                    <div class="pt-4 posts-byline" style="white-space: pre-wrap; overflow-wrap: anywhere; line-height: 1.85;">{{ ucfirst($post->content) }}</div>
                </div>
            </article>
        </div>
        </div>
    </div>
</div>
