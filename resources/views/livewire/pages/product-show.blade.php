<div class="posts-page py-5">
    @guest
    <nav class="mb-4" aria-label="Breadcrumb">
        <a href="{{ route('pages.index') }}">Home</a> / <a href="{{ route('pages.products') }}">Products</a>
        @if ($product->category_slug)
        / <a href="{{ route('pages.product-category', $product->category_slug) }}">{{ $product->category }}</a>
        @endif
    </nav>
    @endguest
    <article class="card posts-card border-0">
        <div class="card-body p-4 p-md-5">
            <p class="posts-eyebrow">Community recommendation</p>
            <h1 class="h2">{{ $product->title }}</h1>
            <p class="text-muted">By {{ $product->user->name }} ? Updated <time datetime="{{ $product->updated_at->toAtomString() }}">{{ $product->updated_at->format('M j, Y') }}</time></p>
            <p class="alert alert-info" role="note">Affiliate disclosure: these links may earn the publisher a commission when you buy, at no extra cost to you. Purchases take place on the merchant's website.</p>
            @if ($product->image_source)
            <img src="{{ $product->image_source }}" alt="{{ $product->image_alt ?: $product->title }}" class="img-fluid rounded mb-4" style="max-height: 420px;" referrerpolicy="no-referrer">
            @endif
            <h2 class="h4">About this recommendation</h2>
            <p style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $product->description }}</p>
            @if ($product->best_for)
            <h2 class="h4 mt-4">Who it suits</h2>
            <p style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $product->best_for }}</p>
            @endif
            @foreach (['pros' => 'Benefits', 'cons' => 'Things to consider'] as $field => $heading)
            @if ($product->{$field})
            <h2 class="h4 mt-4">{{ $heading }}</h2>
            <ul>@foreach (preg_split('/\R/u', $product->{$field}) as $line)
                @if (trim($line) !== '')<li style="overflow-wrap: anywhere;">{{ trim($line) }}</li>@endif
            @endforeach</ul>
            @endif
            @endforeach
            <div class="border-top pt-4 mt-4">
                <h2 class="h4">Check with {{ $product->merchant }}</h2>
                <p class="font-weight-bold">{{ $product->price !== null ? $product->currency.' '.number_format((float) $product->price, 2) : 'See merchant for price' }}</p>
                <p class="small text-muted">Prices are entered by contributors and may change. Confirm availability, delivery costs, compatibility and return terms on the merchant's site.</p>
                <a class="btn btn-primary" href="{{ route(auth()->check() ? 'products.visit' : 'pages.products.visit', $product->id) }}" target="_blank" rel="sponsored nofollow noopener noreferrer">Visit merchant <span class="sr-only">(opens in a new tab)</span></a>
            </div>
        </div>
    </article>
    @guest
    @if ($relatedProducts->isNotEmpty())
    <section class="mt-5" aria-labelledby="related-products-heading">
        <h2 class="h4" id="related-products-heading">More in this category</h2>
        <ul>@foreach ($relatedProducts as $related)
            <li><a href="{{ route('pages.product-show', $related->slug) }}">{{ $related->title }}</a></li>
        @endforeach</ul>
    </section>
    @endif
    @if ($relatedArticles->isNotEmpty())
    <section class="mt-5" aria-labelledby="related-guides-heading">
        <h2 class="h4" id="related-guides-heading">Explore related guides</h2>
        <ul>@foreach ($relatedArticles as $article)
            <li><a href="{{ route('pages.postshow', $article->slug) }}">{{ $article->title }}</a></li>
        @endforeach</ul>
    </section>
    @endif
    <section class="mt-5" aria-labelledby="product-tools-heading">
        <h2 class="h4" id="product-tools-heading">Useful tools before you buy</h2>
        <a class="mr-3" href="{{ route('pages.percentage-calculator') }}">Calculate a discount</a>
        <a href="{{ route('pages.unit-converter') }}">Convert product measurements</a>
    </section>
    @endguest
</div>
