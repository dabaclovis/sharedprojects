<div class="py-4">
    <section class="jumbotron posts-hero text-center w3-round-xlarge">
        <p class="posts-eyebrow">Ideas. Discoveries. Everyday plans.</p>
        <h1 class="display-4 font-weight-bold">About {{ config('app.name') }}</h1>
        <p class="lead mx-auto posts-intro">A place to share what you know, discover useful products, and make time for what matters.</p>
    </section>
    <section class="mb-4"><h2 class="h4">One account, room to create</h2><p>CD brings publishing, affiliate product listings, and personal scheduling together. Whether you are sharing a thoughtful article or planning your next event, you can manage your work from your own dashboard.</p></section>
    <div class="row">
        @foreach ([['fa-pen-nib', 'Share your perspective', 'Write and manage articles, save drafts, and publish stories for the community.', 'pages.articles', 'Explore articles'], ['fa-bag-shopping', 'Discover products', 'Browse product listings and follow clearly marked affiliate links to the seller.', 'pages.products', 'Browse products'], ['fa-calendar-days', 'Plan your time', 'Create events, choose a time zone, and keep your personal schedule in one place.', 'services.calendar', 'Open calendar']] as [$icon, $heading, $copy, $route, $link])
            <div class="col-md-4 mb-4"><section class="card h-100 w3-round-xlarge"><div class="card-body"><i class="fa-solid {{ $icon }} fa-2x text-primary mb-3" aria-hidden="true"></i><h2 class="h5">{{ $heading }}</h2><p class="text-muted">{{ $copy }}</p>@if (auth()->guest() || ! str_starts_with($route, 'pages.'))<a wire:navigate href="{{ route($route) }}">{{ $link }} &rarr;</a>@endif</div></section></div>
        @endforeach
    </div>
    <section class="dashboard-panel p-4"><h2 class="h4">Built around respectful participation</h2><p>Share original work, credit your sources, and be transparent about commercial links. Keep private information out of public posts and treat other contributors with care.</p>@guest <a wire:navigate href="{{ route('pages.policy') }}">Read our community and privacy policy</a> @endguest<span class="mx-2">&middot;</span>@guest <a wire:navigate href="{{ route('pages.contact') }}">Get in touch</a> @endguest</section>
</div>
