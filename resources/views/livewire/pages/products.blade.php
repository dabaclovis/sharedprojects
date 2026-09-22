<div class="posts-page py-5">
    <div class="container">
        <header class="text-center mb-4"><p class="posts-eyebrow">Community recommendations</p><h1 class="h2">Discover useful products</h1><p class="text-muted">Explore recommendations from our community.</p></header>
        <div class="alert alert-info" role="note">Affiliate disclosure: these links may earn the publisher a commission when you buy, at no extra cost to you. Purchases take place on the merchant's website.</div>
        <div class="row mb-4">
            <div class="col-md-8"><label for="catalog-search">Search</label><div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span></div><input id="catalog-search" type="search" class="form-control" placeholder="Product or merchant..." wire:model.live.debounce.300ms="search"></div></div>
            <div class="col-md-4"><label for="catalog-category">Category</label><select id="catalog-category" class="custom-select" wire:model.live="category"><option value="">All categories</option>@foreach ($categories as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach</select></div>
        </div>
        <div class="row">
            @forelse ($products as $product)
                <div class="col-md-6 col-lg-4 mb-4" wire:key="catalog-product-{{ $product->id }}">
                    <article class="card posts-card border-0 h-100">
                        @if ($product->image_source)
                            <div class="product-image-wrap" x-data="{ failed: false }"><img x-show="!failed" x-on:error="failed = true" src="{{ $product->image_source }}" class="product-image" alt="{{ $product->title }}" loading="lazy" referrerpolicy="no-referrer"><i x-show="failed" x-cloak class="fa-solid fa-bag-shopping fa-3x text-muted" aria-hidden="true"></i></div>
                        @else
                            <div class="product-image-wrap"><i class="fa-solid fa-bag-shopping fa-3x text-muted" aria-hidden="true"></i></div>
                        @endif
                        <div class="card-body d-flex flex-column">
                            <p class="posts-category">{{ $product->category ?: 'Recommended' }}</p>
                            <h2 class="h5">{{ ucfirst($product->title) }}</h2>
                            <p class="small text-muted">{{ $product->merchant }} &middot; By {{ $product->user->name }}</p>
                            <details class="mb-3"><summary>Product details</summary><p class="mt-2 product-description">{{ $product->description }}</p></details>
                            <p class="font-weight-bold">{{ $product->price !== null ? $product->currency.' '.number_format((float) $product->price, 2) : 'See merchant for price' }}</p>
                            <p class="small text-muted">Price and availability may change. Updated {{ $product->updated_at->format('M j, Y') }}.</p>
                            <a class="btn btn-primary mt-auto" href="{{ route('products.visit', $product->id) }}" target="_blank" rel="sponsored nofollow noopener noreferrer">Visit merchant <i class="fa-solid fa-arrow-up-right-from-square ml-1" aria-hidden="true"></i><span class="sr-only"> (opens in a new tab)</span></a>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12"><p class="dashboard-panel p-5 text-center">No products found. Try a different search or category.</p></div>
            @endforelse
        </div>
        {{ $products->links() }}
    </div>
</div>
