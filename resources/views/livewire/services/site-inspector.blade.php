<div class="container py-5" @if ($report?->status === 'running') wire:poll.3s="step" @endif>
    <p class="posts-eyebrow">Free tool &middot; No account needed</p>
    <h1 class="h2"><i class="fa-solid {{ $mode === 'seo' ? 'fa-magnifying-glass-chart' : 'fa-diagram-project' }} w3-text-teal mr-2" aria-hidden="true"></i>{{ $mode === 'seo' ? 'SEO audit' : 'Website crawler' }}</h1>
    <p class="text-muted">{{ $mode === 'seo' ? 'Check a page and get a clear list of ways to improve it.' : 'Explore your site, find page errors, and spot repeated titles and descriptions.' }}</p>
    <div class="dashboard-panel p-4 w3-round-xlarge">
        <form wire:submit="start">
            <label for="audit-url">Website address</label>
            <input id="audit-url" class="form-control mb-3" type="url" wire:model="url" placeholder="https://example.com/" maxlength="2048" required>
            @if ($mode === 'crawler')
                <label for="audit-limit">Maximum pages</label>
                <select id="audit-limit" class="custom-select mb-3" wire:model="limit"><option value="10">10 pages</option><option value="20">20 pages</option><option value="50">50 pages</option></select>
                <label for="audit-depth">How many link levels?</label>
                <select id="audit-depth" class="custom-select mb-3" wire:model="maxDepth"><option value="1">1 level — a quick check</option><option value="3">3 levels — a broader check</option><option value="5">5 levels — a deeper check</option></select>
                <label class="d-block mb-3"><input type="checkbox" wire:model="stayInPath"> Stay under the starting URL path (useful for a blog or a site section)</label>
            @endif
            @foreach ($errors->all() as $error)<p class="text-danger" role="alert">{{ $error }}</p>@endforeach
            <p class="small text-muted">Use a public site you own or have permission to check. A copy of the report is saved in our database. Downloads are available in this browser session. Keep this page open while the check runs.</p>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" @disabled($report?->status === 'running')>Start {{ $mode === 'seo' ? 'SEO audit' : 'crawl' }}</button>
            @if ($report?->status === 'running')<button type="button" class="btn btn-outline-secondary ml-2" wire:click="stop" wire:loading.attr="disabled">Pause and keep results</button>@endif
            @if ($report?->status === 'stopped' && (count($report->data['pages']) < $report->data['limit'] || isset($report->data['retry_entry'])))<button type="button" class="btn btn-outline-primary ml-2" wire:click="resume" wire:loading.attr="disabled">Resume crawl</button>@endif
        </form>
        <p class="small text-muted mt-3 mb-0">{{ $mode === 'seo' ? 'Checks one page plus robots.txt and up to five sitemap files.' : 'Checks up to 50 pages and five link levels on the same site address. Sitemap pages start at level zero. Linked query URLs and common file downloads are skipped.' }} Each fetched file is limited to 2 MB. JavaScript is not run. Response time is a server fetch measurement, not a full page-speed score.</p>
        <a class="small d-inline-block mt-2" href="{{ route($mode === 'seo' ? 'services.web-crawler' : 'services.seo-audit') }}">{{ $mode === 'seo' ? 'Need more pages? Open the website crawler' : 'Need one page? Open the SEO audit' }}</a>
    </div>
    @if ($recentReports->count() > 1)
        <details class="mt-4"><summary>Recent reports from this browser session</summary>
            <ul class="mt-2">@foreach ($recentReports as $recent)<li><button type="button" class="btn btn-link text-left" wire:click="openReport('{{ $recent->id }}')" wire:loading.attr="disabled">{{ $recent->url }} &middot; {{ $recent->created_at->format('M j H:i') }} UTC &middot; {{ $recent->status }}</button></li>@endforeach</ul>
        </details>
    @endif
    @if ($report)
        <div class="mt-4" role="status"><strong>{{ $report->status === 'stopped' ? 'Paused' : ucfirst($report->status) }}</strong> &middot; {{ count($report->data['pages']) }} / {{ $report->data['limit'] }} pages checked @if ($report->status === 'running') &middot; Checking {{ $report->data['stage'] }}. Results update as pages finish. Requests are spaced at least {{ $report->data['delay'] }} seconds apart.@endif</div>
        @if (($report->data['retry_at'] ?? 0) > time())<p class="small text-muted">The site requested a wait until {{ \Carbon\CarbonImmutable::createFromTimestampUTC($report->data['retry_at'])->format('M j, H:i:s') }} UTC. Resuming will keep that waiting time.</p>@endif
        <div class="my-3">
            <button type="button" class="btn btn-primary mr-2 mb-2" wire:click="download('html')" wire:loading.attr="disabled"><i class="fa-solid fa-download mr-1" aria-hidden="true"></i>Download site report</button>
            <button type="button" class="btn btn-outline-primary mb-2" wire:click="download('json')" wire:loading.attr="disabled">Download data (JSON)</button>
            <button type="button" class="btn btn-outline-primary mb-2" wire:click="download('csv')" wire:loading.attr="disabled">Download spreadsheet (CSV)</button>
            <p class="small text-muted">The site report opens in your browser. Use Print to save it as a PDF. Downloads include the results collected so far.</p>
        </div>
        @include('reports.site-results')
    @endif
</div>
