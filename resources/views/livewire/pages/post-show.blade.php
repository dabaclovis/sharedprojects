<div class="posts-page py-5">
    @php
    $articleSchema = array_filter([
        '@context' => 'https://schema.org', '@type' => 'Article',
        'headline' => $post->title,
        'description' => $post->meta_description ?: $post->excerpt,
        'datePublished' => $post->published_at->toIso8601String(),
        'dateModified' => $post->updated_at?->toIso8601String(),
        'mainEntityOfPage' => route('pages.postshow', $post->slug),
        'publisher' => ['@type' => 'Organization', 'name' => 'Brotherfall'],
        'image' => $post->featured_image ?: null,
    ]);
    @endphp
    <script type="application/ld+json">{!! json_encode($articleSchema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) !!}</script>
    <div class="container">
        <div class="row">
            <div class="col-12">
                @guest <a wire:navigate class="d-inline-block mb-4" href="{{ route('pages.articles') }}"><i
                        class="fa-solid fa-arrow-left mr-2" aria-hidden="true"></i>Back to articles</a> @endguest
                <article class="card posts-card border-0">
                    <div class="card-body p-4 p-md-5">
                        <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap: .75rem;">
                            <h1 class="h2 font-weight-bold mb-0"
                                style="min-width: 0; overflow-wrap: anywhere; flex: 1 1 240px;">{{ ucfirst($post->title)
                                }}</h1>
                            @if ($post->category && collect(\App\Enums\PostCategory::cases())->contains(fn ($item) => strtolower($item->value) === strtolower($post->category)))
                            <a class="posts-category" href="{{ route('pages.article-category', strtolower($post->category)) }}">{{ ucfirst($post->category) }}</a>
                            @endif
                        </div>
                        <p class="text-muted small mt-3 mb-4">
                            <time datetime="{{ $post->published_at->toIso8601String() }}">{{
                                $post->published_at->format('M j, Y') }}</time>
                        </p>
                        @if ($post->featured_image)
                        <img class="img-fluid rounded mb-4" src="{{ $post->featured_image }}" alt="{{ $post->image_alt }}">
                        @endif
                        @if ($post->tags)
                        <p class="text-muted small">{{ implode(' ? ', $post->tags) }}</p>
                        @endif
                        @if ($post->excerpt)
                        <p class="lead text-muted">{{ ucfirst($post->excerpt) }}</p>
                        @endif
                        <div class="pt-4 posts-byline"
                            style="white-space: pre-wrap; overflow-wrap: anywhere; line-height: 1.85;">{{
                            ucfirst($post->content) }}</div>
                    </div>
                </article>
                @if ($relatedPosts->isNotEmpty())
                <section class="mt-5" aria-labelledby="related-heading">
                    <h2 id="related-heading" class="h4">Related articles</h2>
                    <ul>@foreach ($relatedPosts as $related)
                        <li><a href="{{ route('pages.postshow', $related->slug) }}">{{ $related->title }}</a></li>
                    @endforeach</ul>
                </section>
                @endif
                <section class="mt-5" aria-labelledby="tools-heading">
                    <h2 id="tools-heading" class="h4">Tools and recommendations</h2>
                    @guest <a class="mr-3" href="{{ route('pages.products') }}">Explore community product recommendations</a> @endguest
                    <a class="mr-3" href="{{ route('pages.word-counter') }}">Word counter</a>
                    <a class="mr-3" href="{{ route('pages.seo-audit') }}">SEO audit</a>
                    <a class="mr-3" href="{{ route('pages.percentage-calculator') }}">Percentage calculator</a>
                    <a href="{{ route('pages.timezone-converter') }}">Time zone converter</a>
                </section>
                <section class="mt-5" aria-labelledby="post-comments-heading">
                    <h2 id="post-comments-heading" class="h4 mb-3">Comments</h2>
                    @if (session('commentStatus'))
                    <p class="alert alert-success py-2" role="status">{{ session('commentStatus') }}</p>
                    @endif
                    @auth
                    @if (auth()->user()->status === 'active')
                    <form wire:submit="submitComment" class="mb-4">
                        <label for="new-post-comment">Add a comment</label>
                        <textarea id="new-post-comment" class="form-control mb-2" wire:model="newComment" rows="3"
                            maxlength="3000" required></textarea>
                        @error('newComment')<p class="text-danger small" role="alert">{{ $message }}</p>@enderror
                        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled"
                            wire:target="submitComment">Post comment</button>
                    </form>
                    @endif
                    @else
                    <p class="text-muted">Sign in to join the discussion. <a wire:navigate
                            href="{{ route('auth.login') }}">Sign in</a></p>
                    @endauth

                    <div class="post-comments">
                        @forelse ($comments as $comment)
                        <article class="border-top py-3" wire:key="post-comment-{{ $comment->id }}">
                            <p class="small text-muted mb-1">{{ $comment->user->username ?: $comment->user->name }}
                                &middot; {{ $comment->created_at->format('M j, Y H:i') }}</p>
                            <p class="mb-2" style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $comment->body }}
                            </p>
                            @auth
                            @if (auth()->user()->status === 'active')
                            @if ($replyToId === $comment->id)
                            <form wire:submit="submitReply" class="mb-3">
                                <label for="reply-body-{{ $comment->id }}">Reply</label>
                                <textarea id="reply-body-{{ $comment->id }}" class="form-control mb-2"
                                    wire:model="replyBody" rows="2" maxlength="3000" required></textarea>
                                @error('replyBody')<p class="text-danger small" role="alert">{{ $message }}</p>@enderror
                                <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled"
                                    wire:target="submitReply">Post reply</button>
                                <button type="button" class="btn btn-light btn-sm"
                                    wire:click="cancelReply">Cancel</button>
                            </form>
                            @else
                            <button type="button" class="btn btn-link btn-sm px-0"
                                wire:click="openReply({{ $comment->id }})">Reply</button>
                            @endif
                            @endif
                            @endauth
                            @foreach ($comment->replies as $reply)
                            <div class="ml-3 ml-md-4 border-left pl-3 py-2"
                                wire:key="post-comment-reply-{{ $reply->id }}">
                                <p class="small text-muted mb-1">{{ $reply->user->username ?: $reply->user->name }}
                                    &middot; {{ $reply->created_at->format('M j, Y H:i') }}</p>
                                <p class="mb-0" style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $reply->body
                                    }}</p>
                            </div>
                            @endforeach
                        </article>
                        @empty
                        <p class="text-muted">No comments yet.</p>
                        @endforelse
                    </div>
                    @if ($comments->hasPages())
                    <div class="mt-3">{{ $comments->links(data: ['scrollTo' => '#post-comments-heading']) }}</div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</div>