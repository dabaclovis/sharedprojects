<div class="container py-5">
    <header class="dashboard-welcome p-4 mb-4 d-flex flex-wrap justify-content-between align-items-center">
        <div><h1 class="h3">My affiliate products</h1><p class="text-muted mb-2">Recommend useful products and manage your affiliate links.</p>@guest <a wire:navigate href="{{ route('pages.products') }}">View the public catalog</a> @endguest</div>
        <button class="btn btn-primary mt-2" wire:click="create">Add product</button>
    </header>
    @if (session('productStatus')) <div class="alert alert-success" role="status">{{ session('productStatus') }}</div> @endif
    <div class="row mb-4">
        <div class="col-sm-8"><label for="product-search">Search products</label><input id="product-search" type="search" class="form-control" placeholder="Search your product titles..." wire:model.live.debounce.300ms="search"></div>
        <div class="col-sm-4"><label for="product-filter">Status</label><select id="product-filter" class="custom-select" wire:model.live="filter"><option value="">All statuses</option><option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option></select></div>
    </div>
    @forelse ($products as $product)
        <article class="card w3-round-xlarge mb-3" wire:key="product-{{ $product->id }}">
            <div class="card-body py-3 d-flex flex-wrap align-items-center" style="gap: 1rem;">
                <div class="flex-grow-1"><h2 class="h5 mb-1">{{ ucfirst($product->title) }}</h2><span class="small text-muted">{{ $product->merchant }} &middot; {{ ucfirst($product->status) }} &middot; {{ $product->clicks }} outbound clicks</span></div>
                <div class="ml-auto"><button class="btn btn-outline-primary btn-sm" wire:click="edit({{ $product->id }})">Edit</button> <button class="btn btn-outline-danger btn-sm" wire:click="delete({{ $product->id }})" wire:confirm="Delete this product listing?" wire:loading.attr="disabled">Delete</button></div>
            </div>
        </article>
    @empty
        <div class="dashboard-panel p-5 text-center"><h2 class="h5">No products found</h2><p class="text-muted">Add a product or adjust your filters.</p></div>
    @endforelse
    <div class="mt-4">{{ $products->links() }}</div>

    @if ($showEditor)
        <div class="article-modal-backdrop" x-data x-init="$nextTick(() => $refs.firstField.focus())" @keydown.escape.window="$wire.cancel()" wire:key="product-editor">
            <section class="article-modal affiliate-modal card" role="dialog" aria-modal="true" aria-labelledby="product-editor-heading" x-trap.inert.noscroll="true">
                <div class="card-header d-flex justify-content-between align-items-center"><h2 id="product-editor-heading" class="h5 mb-0">{{ $productId ? 'Edit product' : 'Add product' }}</h2><button type="button" class="close" wire:click="cancel" aria-label="Close">&times;</button></div>
                <form wire:submit="save" class="card-body" novalidate>
                    <label for="product-title">Product name</label><input id="product-title" x-ref="firstField" class="form-control mb-2" wire:model="title" required maxlength="255">
                    @error('title') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                    <label for="product-description">Description</label><textarea id="product-description" class="form-control mb-2" wire:model="description" rows="2" required></textarea>
                    @error('description') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                    <label for="product-category">Category (optional)</label>
                    <select id="product-category" class="custom-select mb-2" wire:model="category">
                        <option value="">No category</option>
                        @if ($category !== '' && !\App\Enums\ProductCategory::tryFrom($category))
                            <option value="{{ $category }}" disabled>{{ $category }} (choose a new category)</option>
                        @endif
                        @foreach (\App\Enums\ProductCategory::cases() as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </select>
                    @error('category') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                    @foreach (['merchant' => 'Merchant / store', 'affiliate_url' => 'Affiliate URL'] as $field => $label)
                        <label for="product-{{ $field }}">{{ $label }}</label><input id="product-{{ $field }}" type="{{ str_ends_with($field, '_url') ? 'url' : 'text' }}" class="form-control mb-2" wire:model="{{ $field }}">
                        @error($field) <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                    @endforeach
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="product-image-url">Image URL (optional)</label>
                            <input id="product-image-url" type="url" class="form-control" wire:model="image_url" placeholder="https://example.com/product.jpg">
                            <small class="form-text text-muted">Use an HTTPS image link, or upload an image.</small>
                            @error('image_url') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="product-image-upload">Upload image (optional)</label>
                            <input id="product-image-upload" type="file" class="form-control-file" wire:model="image" accept="image/jpeg,image/png,image/webp">
                            <small class="form-text text-muted">JPG, PNG, or WebP, up to 5 MB. Uploaded images take priority over the URL.</small>
                            <span class="small text-muted" wire:loading wire:target="image" role="status">Uploading image...</span>
                            @error('image') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                            @if ($currentImageUrl)
                                <img src="{{ $currentImageUrl }}" alt="Current product image" class="img-thumbnail mt-2" style="max-height: 100px;">
                                <div class="form-check mt-2"><input id="remove-product-image" type="checkbox" class="form-check-input" wire:model="removeImage"><label for="remove-product-image" class="form-check-label">Remove saved upload and use URL</label></div>
                            @endif
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6"><label for="product-price">Price (optional)</label><input id="product-price" type="number" min="0" step="0.01" class="form-control mb-2" wire:model="price">@error('price') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror</div>
                        <div class="col-sm-6"><label for="product-currency">Currency</label><select id="product-currency" class="custom-select mb-2" wire:model="currency">@foreach (\App\Livewire\Services\Affiliates::CURRENCIES as $code)<option value="{{ $code }}">{{ $code }}</option>@endforeach</select>@error('currency') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror</div>
                    </div>
                    <label for="product-status">Status</label><select id="product-status" class="custom-select mb-2" wire:model="status"><option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option></select>
                    @error('status') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                    <p class="small text-muted mt-2">Published products appear in the public catalog with an affiliate disclosure. Prices are entered manually.</p>
                    <div class="d-flex justify-content-end"><button type="button" class="btn btn-light mr-2" wire:click="cancel">Cancel</button><button class="btn btn-primary" wire:loading.attr="disabled" wire:target="save,image">Save product</button></div>
                </form>
            </section>
        </div>
    @endif
</div>
