<div class="posts-page py-5">
    <div class="container">
        <header class="text-center mb-4">
            <p class="posts-eyebrow">Community recommendations</p>
            <h1 class="h2">{{ $landingCategory ? $landingCategory.' recommendations' : 'Discover useful products' }}</h1>
            <p class="text-muted">Find useful products for everyday life and work. Read why contributors recommend them, who they suit, and what to consider before buying.</p>
        </header>
        <div class="alert alert-info" role="note">Affiliate disclosure: these links may earn the publisher a commission
            when you buy, at no extra cost to you. Purchases take place on the merchant's website.</div>
        @guest
        @if ($categoryLinks->isNotEmpty())
        <nav class="mb-4" aria-label="Product categories">
            <a class="mr-3" href="{{ route('pages.products') }}">All recommendations</a>
            @foreach ($categoryLinks as $item)
            <a class="mr-3 d-inline-block" href="{{ route('pages.product-category', \Illuminate\Support\Str::slug($item->value)) }}">{{ $item->label() }}</a>
            @endforeach
        </nav>
        @endif
        @endguest
        <div class="row mb-4">
            <div class="col-md-8"><label for="catalog-search">Search</label>
                <div class="input-group">
                    <div class="input-group-prepend"><span class="input-group-text"><i
                                class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span></div><input
                        id="catalog-search" type="search" class="form-control" placeholder="Product or merchant..."
                        wire:model.live.debounce.300ms="search">
                </div>
            </div>
            <div class="col-md-4"><label for="catalog-category">Category</label><select id="catalog-category"
                    class="custom-select" wire:model.live="category" @disabled($landingCategory !== '')>
                    <option value="">All categories</option>@foreach ($categories as $name)<option value="{{ $name }}">
                        {{ $name }}</option>@endforeach
                </select></div>
        </div>
        <div class="row">
            @forelse ($products as $product)
            <div class="col-md-6 col-lg-4 mb-4" wire:key="catalog-product-{{ $product->id }}">
                <article class="card posts-card border-0 h-100">
                    @if ($product->image_source)
                    <div class="product-image-wrap" x-data="{ failed: false }"><img x-show="!failed"
                            x-on:error="failed = true" src="{{ $product->image_source }}" class="product-image"
                            alt="{{ $product->image_alt ?: $product->title }}" loading="lazy" referrerpolicy="no-referrer"><i x-show="failed"
                            x-cloak class="fa-solid fa-bag-shopping fa-3x text-muted" aria-hidden="true"></i></div>
                    @else
                    <div class="product-image-wrap"><i class="fa-solid fa-bag-shopping fa-3x text-muted"
                            aria-hidden="true"></i></div>
                    @endif
                    <div class="card-body d-flex flex-column">
                        <p class="posts-category">{{ $product->category ?: 'Recommended' }}</p>
                        <h2 class="h5">@guest <a href="{{ route('pages.product-show', $product->slug) }}">{{ ucfirst($product->title) }}</a> @else {{ ucfirst($product->title) }} @endguest</h2>
                        <p class="small text-muted">{{ $product->merchant }} &middot; By {{ $product->user->name }}</p>
                        <p>{{ \Illuminate\Support\Str::limit($product->description, 180) }}</p>
                        @guest <a class="mb-3" href="{{ route('pages.product-show', $product->slug) }}">Read recommendation</a> @endguest
                        <details class="mb-3">
                            <summary>Product details</summary>
                            <p class="mt-2 product-description">{{ $product->description }}</p>
                        </details>
                        <p class="font-weight-bold">{{ $product->price !== null ? $product->currency.'
                            '.number_format((float) $product->price, 2) : 'See merchant for price' }}</p>
                        <p class="small text-muted">Price and availability may change. Updated {{
                            $product->updated_at->format('M j, Y') }}.</p>
                        <a class="btn btn-primary mt-auto"
                            href="{{ auth()->check() ? route('products.visit', $product->id) : route('pages.products.visit', $product->id) }}"
                            target="_blank" rel="sponsored nofollow noopener noreferrer">Visit merchant <i
                                class="fa-solid fa-arrow-up-right-from-square ml-1" aria-hidden="true"></i><span
                                class="sr-only"> (opens in a new tab)</span></a>
                    </div>
                </article>
            </div>
            @empty
            <div class="col-12">
                <div class="dashboard-panel p-5 text-center">
                    @if ($search !== '' || ($category !== '' && $category !== $landingCategory))
                    <h2 class="h5">No products found</h2>
                    <p>Try a different search or category.</p>
                    <button type="button" class="btn btn-outline-primary" wire:click="clearFilters">Clear filters</button>
                    @else
                    <h2 class="h5">Recommendations are on the way</h2>
                    <p>{{ $landingCategory ? 'There are no published recommendations in this category yet.' : 'Our community has not published any product recommendations yet.' }}</p>
                    <a href="{{ route('users.products') }}">Share a useful product</a>
                    @endif
                </div>
            </div>
            @endforelse
        </div>
        {{ $products->links() }}
        <section class="mt-5" aria-labelledby="buying-heading">
            <h2 id="buying-heading" class="h4">Make a more informed choice</h2>
            <p>Start with the problem you want to solve. Compare suitability, compatibility, total cost and return terms. Community recommendations reflect the contributor's perspective; check current details with the merchant before buying.</p>
            @guest <a class="mr-3" href="{{ route('pages.article-category', 'reviews') }}">Read reviews and buying guides</a>
            <a class="mr-3" href="{{ route('pages.percentage-calculator') }}">Compare discounts with our percentage calculator</a>
            <a href="{{ route('pages.unit-converter') }}">Check measurements with our unit converter</a> @endguest
        </section>
    </div>
</div>