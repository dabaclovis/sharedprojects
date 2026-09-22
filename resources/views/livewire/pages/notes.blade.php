<div class="quotes-page py-4">
    <header class="dashboard-welcome p-4 p-md-5 mb-4">
        <p class="small text-uppercase font-weight-bold text-muted mb-2">Words worth sharing</p>
        <h1 class="font-weight-bold">Community quotes</h1>
        <p class="mb-0">A little inspiration, a fresh perspective. Read and share a quote — no account needed.</p>
    </header>

    <div class="row">
        <aside class="col-lg-4 mb-4">
            <section class="dashboard-panel p-4" aria-labelledby="share-quote-heading">
                <h2 id="share-quote-heading" class="h5 mb-3 w3-center">Share a quote</h2>
                <p class="small text-muted w3-center">Your quote will be visible to everyone.</p>
                @if (session()->has('quote-saved'))
                <div class="alert alert-success" role="status">{{ session('quote-saved') }}</div>
                @endif
                <form wire:submit="save">
                    <div class="form-group">
                        <label for="quote-content">Quote <span class="text-danger" aria-hidden="true">*</span></label>
                        <textarea id="quote-content" wire:model="content" rows="5" maxlength="5000" required
                            class="form-control @error('content') is-invalid @enderror"
                            placeholder="Write the words that inspired you..."
                            aria-describedby="quote-content-help @error('content') quote-content-error @enderror"></textarea>
                        <small id="quote-content-help" class="form-text text-muted">Up to 5,000 characters. We add the
                            quotation marks for you.</small>
                        @error('content')<div id="quote-content-error" class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @foreach (['author' => 'Author', 'title' => 'Title'] as $field => $label)
                    <div class="form-group">
                        <label for="quote-{{ $field }}">{{ $label }} <span
                                class="small text-muted">(optional)</span></label>
                        <input id="quote-{{ $field }}" wire:model="{{ $field }}" maxlength="255"
                            class="form-control form-control-sm @error($field) is-invalid @enderror" @error($field)
                            aria-describedby="quote-{{ $field }}-error" @enderror>
                        @error($field)<div id="quote-{{ $field }}-error" class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @endforeach
                    <div class="row">
                        @foreach (['licon' => 'Left icon', 'ricon' => 'Right icon'] as $field => $label)
                        <div class="col-sm-6 form-group">
                            <label for="quote-{{ $field }}">{{ $label }}</label>
                            <select id="quote-{{ $field }}" wire:model.live="{{ $field }}"
                                class="custom-select custom-select-sm @error($field) is-invalid @enderror"
                                aria-describedby="quote-{{ $field }}-error">
                                @foreach ($iconOptions as $icon => $name)
                                <option value="{{ $icon }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error($field)
                            <div id="quote-{{ $field }}-error" class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-between align-items-center rounded bg-light p-3 mb-3"
                        aria-label="Selected icon preview">
                        <i class="fa-solid {{ isset($iconOptions[$licon]) ? $licon : 'fa-quote-left' }} quote-decoration"
                            aria-hidden="true"></i>
                        <span class="small text-muted mx-2">Your quote</span>
                        <i class="fa-solid {{ isset($iconOptions[$ricon]) ? $ricon : 'fa-quote-right' }} quote-decoration"
                            aria-hidden="true"></i>
                    </div>
                    <details class="mb-3" @if ($errors->hasAny(['source', 'category', 'tags', 'language'])) open @endif>
                        <summary class="small text-primary mb-3">More details (optional)</summary>
                        <div class="form-group">
                            <label for="quote-category">Category</label>
                            <select id="quote-category" wire:model="category"
                                class="form-control form-control-sm @error('category') is-invalid @enderror"
                                @error('category') aria-describedby="quote-category-error" @enderror>
                                <option value="">Choose a category (optional)</option>
                                @foreach ($categories as $quoteCategory)
                                <option value="{{ $quoteCategory->value }}">{{ $quoteCategory->label() }}</option>
                                @endforeach
                            </select>
                            @error('category')
                            <div id="quote-category-error" class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @foreach (['source' => ['Source', 'e.g. Book title, speech or website'], 'tags' => ['Tags,
                        separated by commas', 'e.g. kindness, courage, growth'], 'language' => ['Language', 'e.g.
                        English']] as $field => [$label, $placeholder])
                        <div class="form-group">
                            <label for="quote-{{ $field }}">{{ $label }}</label>
                            <x-forms.inputs id="quote-{{ $field }}" wire:model="{{ $field }}" maxlength="255"
                                :class="'form-control form-control-sm'.($errors->has($field) ? ' is-invalid' : '')"
                                :placeholder="$placeholder" aria-describedby="quote-{{ $field }}-error" />
                            @error($field)<div id="quote-{{ $field }}-error" class="invalid-feedback">{{ $message }}
                            </div>@enderror
                        </div>
                        @endforeach
                    </details>
                    <button class="btn btn-primary btn-block" type="submit" wire:loading.attr="disabled"
                        wire:target="save">
                        <span wire:loading.remove wire:target="save">Share quote</span>
                        <span wire:loading wire:target="save">Sharing...</span>
                    </button>
                </form>
            </section>
        </aside>

        <section class="col-lg-8" aria-labelledby="quote-library-heading">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                <h2 id="quote-library-heading" class="h5">Explore quotes</h2>
                <span class="small text-muted" role="status">{{ number_format($quotes->total()) }} {{ $quotes->total()
                    === 1 ? 'quote' : 'quotes' }}</span>
            </div>
            <label for="quote-search" class="sr-only">Search quotes, titles, authors or tags</label>
            <input id="quote-search" type="search" class="form-control mb-4" maxlength="200"
                wire:model.live.debounce.300ms="search" placeholder="Search quotes, titles, authors or tags...">
            <div wire:loading.class="posts-loading" wire:target="search,gotoPage,nextPage,previousPage">
                @forelse ($quotes as $quote)
                <article class="quote-card dashboard-panel mb-3" wire:key="quote-{{ $quote->id }}">
                    <div class="quote-icon-panel" aria-hidden="true">
                        <i
                            class="fa-solid {{ isset($iconOptions[$quote->licon]) ? $quote->licon : 'fa-quote-left' }} quote-decoration"></i>
                    </div>
                    <div class="quote-card-body">
                        @if ($quote->title)<h3 class="h6 font-weight-bold mb-3">{{ Str::ucfirst($quote->title) }}</h3>
                        @endif
                        <blockquote class="mb-0">
                            <p class="quote-text mb-0">{{ $quote->content }}</p>
                            <footer class="small text-muted mt-3">
                                &mdash; {{ $quote->author ?: 'Anonymous' }}
                                @if ($quote->source)
                                <cite class="d-block mt-1">{{ $quote->source }}</cite>
                                @endif
                            </footer>
                        </blockquote>
                        @if ($quote->category || $quote->language || $quote->tags)
                        <div class="quote-metadata mt-3">
                            @if ($quote->category)<span class="badge badge-light">{{ $quote->category }}</span>@endif
                            @if ($quote->language)<span class="badge badge-light">{{ $quote->language }}</span>@endif
                            @if ($quote->tags)<span class="small text-muted">{{ $quote->tags }}</span>@endif
                        </div>
                        @endif
                    </div>
                    <div class="quote-icon-panel" aria-hidden="true">
                        <i
                            class="fa-solid {{ isset($iconOptions[$quote->ricon]) ? $quote->ricon : 'fa-quote-right' }} quote-decoration quote-decoration-right"></i>
                    </div>
                </article>
                @empty
                <div class="dashboard-panel p-5 text-center">
                    <i class="fa-solid fa-quote-left fa-2x text-muted mb-3" aria-hidden="true"></i>
                    <h3 class="h5">{{ $search !== '' ? 'No matching quotes' : 'Be the first to share a quote' }}</h3>
                    <p class="text-muted">{{ $search !== '' ? 'Try another author, phrase or tag.' : 'Inspire someone
                        with a few meaningful words.' }}</p>
                    @if ($search !== '')<button type="button" class="btn btn-outline-primary btn-sm"
                        wire:click="$set('search', '')">Clear search</button>@endif
                </div>
                @endforelse
            </div>
            <div class="mt-4 text-center">{{ $quotes->links('pagination::bootstrap-4') }}</div>
        </section>
    </div>
</div>