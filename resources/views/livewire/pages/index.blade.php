<div class="posts-page">
    @if ($sponsors->isNotEmpty())
        <section class="py-4" aria-labelledby="sponsors-heading"><h2 id="sponsors-heading" class="h5">Sponsored placements</h2><div class="row">@foreach ($sponsors as $sponsor)<article class="col-md-6 mb-3"><div class="dashboard-panel p-4 h-100"><span class="badge badge-light mb-2">Sponsored</span><p class="small text-muted mb-1">{{ $sponsor->sponsor_name }}</p><h3 class="h5" style="overflow-wrap: anywhere;">{{ $sponsor->sponsor_title }}</h3><p style="overflow-wrap: anywhere;">{{ $sponsor->sponsor_description }}</p><a href="{{ $sponsor->sponsor_url }}" rel="sponsored noopener noreferrer" target="_blank">Visit sponsor <span class="sr-only">(opens in a new tab)</span></a></div></article>@endforeach</div></section>
    @endif
    <div class="dashboard-panel p-3 my-3 d-flex flex-wrap justify-content-between align-items-center" style="gap: .5rem;"><span>Need a website review or want to sponsor this community?</span><a wire:navigate class="btn btn-outline-primary btn-sm" href="{{ route('pages.business') }}">Explore business services</a></div>
    <section class="jumbotron jumbotron-fluid text-center posts-hero mb-0" aria-labelledby="home-heading">
        <div class="container py-4 py-md-5">
            <p class="posts-eyebrow mb-3">A space for curious minds</p>
            <h1 id="home-heading" class="display-4 font-weight-bold">Good stories. Fresh perspectives.</h1>
            <p class="lead mx-auto mt-3 mb-4 posts-intro">Share your stories, connect people with useful discoveries, and bring your next event to life.</p>
            @guest <a wire:navigate class="btn btn-primary px-4 py-2" href="{{ route('pages.articles') }}">Explore articles</a> @endguest
            <a class="btn btn-outline-primary px-4 py-2 ml-2" href="#services">Our services</a>
        </div>
    </section>
    <section id="services" class="container py-5" aria-labelledby="services-heading">
        <div class="text-center mb-5">
            <p class="posts-eyebrow text-muted">What we offer</p>
            <h2 id="services-heading" class="h3 font-weight-bold">One place for your ideas and plans</h2>
            <p class="text-muted">Discover our services for publishing, sharing, and organizing.</p>
        </div>
        <div class="row">
            @foreach ([['pages.seo-audit', 'SEO audit', 'Check a page and download clear recommendations to improve your site.', 'fa-magnifying-glass-chart'], ['pages.web-crawler', 'Website crawler', 'Explore your pages, find errors, and download a detailed site report.', 'fa-diagram-project'], ['pages.quote-builder', 'Freelance quote builder', 'Price your project, include expenses, and create a client estimate with a deposit breakdown.', 'fa-file-invoice-dollar'], ['pages.text-toolkit', 'Text toolkit', 'Edit text, convert case, clean up lists, and download your polished draft.', 'fa-pen-to-square'], ['pages.word-counter', 'Word counter', 'Count words and characters, and estimate reading time for your next article or assignment.', 'fa-pen'], ['pages.timezone-converter', 'Time zone converter', 'Convert a date and time between cities to plan calls and events around the world.', 'fa-clock'], ['pages.percentage-calculator', 'Percentage calculator', 'Calculate percentages, proportions, and percentage increases or decreases.', 'fa-percent'], ['pages.unit-converter', 'Unit converter', 'Convert length and weight between metric and imperial measurements.', 'fa-ruler'], ['pages.date-difference', 'Date difference', 'Find the number of days and weeks between two dates.', 'fa-calendar-days']] as [$toolRoute, $toolTitle, $toolDescription, $toolIcon])
                <div class="col-md-6 mb-4">
                    <article class="card h-100 posts-card border-0">
                        <div class="card-body p-4 d-flex flex-column">
                            <p class="small text-primary"><i class="fa-solid {{ $toolIcon }} mr-2" aria-hidden="true"></i>Free · No login required</p>
                            <h3 class="h5 font-weight-bold">{{ $toolTitle }}</h3>
                            <p class="text-muted">{{ $toolDescription }}</p>
                            <a wire:navigate class="btn btn-outline-primary mt-auto" href="{{ route($toolRoute) }}">Open {{ strtolower($toolTitle) }}</a>
                        </div>
                    </article>
                </div>
            @endforeach
            <div class="col-md-4 mb-4">
                <article class="card h-100 posts-card border-0">
                    <div class="posts-art posts-art-community py-4" style="flex-basis: auto;" aria-hidden="true"><i class="fa-solid fa-pen-to-square"></i></div>
                    <div class="card-body p-4 d-flex flex-column">
                        <h3 class="h5 font-weight-bold">Post management</h3>
                        <p class="text-muted">Create articles, refine your drafts, and publish stories for your community. Keep your content organized in one place.</p>
                        <a wire:navigate class="btn btn-outline-primary mt-auto" href="{{ auth()->check() ? route('users.articles') : route('auth.register') }}">{{ auth()->check() ? 'Manage your posts' : 'Start publishing' }}</a>
                    </div>
                </article>
            </div>
            <div class="col-md-4 mb-4">
                <article class="card h-100 posts-card border-0">
                    <div class="posts-art posts-art-ideas py-4" style="flex-basis: auto;" aria-hidden="true"><i class="fa-solid fa-link"></i></div>
                    <div class="card-body p-4 d-flex flex-column">
                        <h3 class="h5 font-weight-bold">Affiliate link posting</h3>
                        <p class="text-muted">Share product recommendations and affiliate links with your audience, with clear information about what you recommend.</p>
                        <a wire:navigate class="mt-auto" href="{{ auth()->check() ? route('users.products') : route('pages.products') }}">{{ auth()->check() ? 'Manage affiliate products' : 'Browse products' }} <i class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i></a>
                    </div>
                </article>
            </div>
            <div class="col-md-4 mb-4">
                <article class="card h-100 posts-card border-0">
                    <div class="posts-art posts-art-updates py-4" style="flex-basis: auto;" aria-hidden="true"><i class="fa-solid fa-calendar-days"></i></div>
                    <div class="card-body p-4 d-flex flex-column">
                        <h3 class="h5 font-weight-bold">Event scheduling</h3>
                        <p class="text-muted">Plan upcoming events and coordinate the dates and details that help bring your community together.</p>
                        <a wire:navigate class="mt-auto" href="{{ route('users.calendar') }}">Open your calendar <i class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i></a>
                    </div>
                </article>
            </div>
        </div>
    </section>
</div>
