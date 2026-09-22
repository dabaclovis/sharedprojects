@php
    $data = $report->data;
    $pages = $data['pages'];
    $issues = collect($pages)->flatMap(fn ($page) => $page['issues']);
    $summary = app(\App\Services\SiteAudit\ReportSummary::class)->build($data);
@endphp
<section class="site-report">
    <h2>Report for {{ $report->url }}</h2>
    <p>Started {{ $report->created_at->format('M j, Y H:i') }} UTC &middot; Status: {{ $report->status }}</p>
    <div class="row age-results">
        @foreach (['Pages checked' => count($pages), 'Fix first' => $issues->where('priority', 'Fix first')->count(), 'Other findings' => $issues->where('priority', '!=', 'Fix first')->count(), 'Blocked by robots.txt' => $data['skipped']] as $label => $value)
            <div class="col-sm-6 col-lg-3 mb-3"><div class="age-result-card age-result-card--{{ ['teal', 'amber', 'blue', 'purple'][$loop->index] }} w3-card w3-round-xlarge w3-padding-large"><p class="h3 age-result-value">{{ $value }}</p><p class="mb-0">{{ $label }}</p></div></div>
        @endforeach
    </div>
    <p>{{ $summary['pending'] }} queued URLs remain unchecked. @if ($summary['average_ms'] !== null) Average successful fetch: {{ $summary['average_ms'] }} ms.@endif This is a limited sample of the site.</p>
    @if ($summary['actions'])
        <h3 class="h5 mt-3">Your action plan</h3>
        <p>Start at the top. Each item shows the pages affected so you can make targeted changes.</p>
        @foreach (array_slice($summary['actions'], 0, ($isDownload ?? false) ? count($summary['actions']) : 12) as $action)
            <details class="mb-2"><summary><strong>{{ $action['priority'] }}</strong> &middot; {{ count($action['urls']) }} {{ count($action['urls']) === 1 ? 'page' : 'pages' }} &middot; {{ $action['message'] }}</summary><ul>@foreach ($action['urls'] as $affected)<li style="overflow-wrap:anywhere">{{ $affected }}</li>@endforeach</ul></details>
        @endforeach
        @if (count($summary['actions']) > 12 && !($isDownload ?? false))<p class="small">Showing the first 12 action groups. Download the report for the full list.</p>@endif
    @endif
    @if ($summary['broken'])
        <h3 class="h5 mt-4">Failed pages and where they are linked</h3>
        @foreach ($summary['broken'] as $broken)
            <div class="alert alert-warning"><strong style="overflow-wrap:anywhere">{{ $broken['url'] }}</strong> &middot; HTTP {{ $broken['status'] ?: 'unavailable' }}
                @if ($broken['sources'])<p class="mb-1">Review links on these pages:</p><ul>@foreach ($broken['sources'] as $source)<li style="overflow-wrap:anywhere">{{ $source }}</li>@endforeach</ul>@else<p class="mb-0">No linking page was found in this sample. This URL may be the start page or come from a sitemap.</p>@endif
            </div>
        @endforeach
    @endif
    @if ($summary['blocked'])<details class="mb-3"><summary>URLs skipped because of robots.txt</summary><ul>@foreach ($summary['blocked'] as $blocked)<li style="overflow-wrap:anywhere">{{ $blocked }}</li>@endforeach</ul></details>@endif
    <h3 class="h5 mt-3">What to do next</h3>
    @if (count($pages))
        <ol>
            <li>Start with “Fix first” findings. Restore failed pages and add missing titles where needed.</li>
            <li>Review indexing rules. Some pages are meant to stay out of search.</li>
            <li>Improve descriptions, headings, and image text where the report points out a gap.</li>
            <li>Run another check after your changes. Use Search Console to check actual search indexing and performance.</li>
        </ol>
    @else <p>No page results yet. Check the crawl notes below for progress or the reason the check stopped.</p>@endif
    <h3 class="h5">Crawl notes</h3>
    <ul>@foreach ($data['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul>
    <p class="small text-muted">This report checks fetched HTML, not rendered pages, rankings, backlinks, full accessibility, or Core Web Vitals. A finding is a prompt to review the page, not proof of a ranking problem. External links are listed but are not fetched. Structured data checks cover JSON syntax, not schema rules or rich-result eligibility.</p>
    @foreach ($pages as $page)
        <article class="dashboard-panel p-4 my-3 page-report">
            <h3 class="h5" style="overflow-wrap:anywhere">{{ $page['url'] }}</h3>
            <p>HTTP {{ $page['status'] ?: 'unavailable' }} &middot; {{ $page['ms'] }} ms &middot; Link depth {{ $page['depth'] }}</p>
            @if (!empty($page['redirect']))<p>Redirects to: {{ $page['redirect'] }}</p>@endif
            <dl>
                <dt>Title</dt><dd>{{ $page['title'] ?: 'Not found or not checked' }}</dd>
                <dt>Description</dt><dd>{{ $page['description'] ?: 'Not found or not checked' }}</dd>
                @if (isset($page['indexing']))
                    @if (!empty($page['title']))
                        <dt>Search preview</dt><dd class="p-3 w3-pale-blue w3-round-large"><span class="small">{{ $page['url'] }}</span><br><strong>{{ $page['title'] }}</strong><br>{{ $page['description'] ?: 'No description was found.' }}<br><small>Example only. Search engines can choose different text.</small></dd>
                    @endif
                    <dt>Indexing rule</dt><dd>{{ $page['indexing'] }}. This does not confirm search engine indexing.</dd>
                    <dt>Canonical URL</dt><dd>{{ $page['canonical'] ?: 'Not found' }}</dd>
                    <dt>Page details</dt><dd>{{ $page['h1'] }} main headings &middot; about {{ $page['words'] }} words &middot; {{ $page['images'] }} images &middot; {{ $page['missing_alt'] }} missing alt attributes &middot; {{ $page['structured_data_blocks'] }} JSON-LD blocks</dd>
                    @if (isset($page['invalid_json_ld']))<dt>Additional checks</dt><dd>{{ $page['invalid_json_ld'] }} invalid JSON-LD blocks &middot; {{ $page['mixed_content'] }} HTTP resources &middot; {{ $page['empty_links'] }} links without an obvious label @if (isset($page['bytes'])) &middot; {{ number_format($page['bytes'] / 1024, 1) }} KB of HTML @endif</dd>@endif
                    @if (isset($page['internal_links']))<dt>Link overview</dt><dd>{{ $page['internal_links'] }} links within this site &middot; {{ $page['external_links'] }} links to other sites &middot; {{ count($page['follow_links']) }} links allowed for follow-up checks before crawl limits</dd>@endif
                @endif
            </dl>
            <h4 class="h6">Findings and recommendations</h4>
            @forelse ($page['issues'] as $issue)<p><strong>{{ $issue['priority'] }}:</strong> {{ $issue['message'] }}</p>@empty<p>No issues found in the checks performed on this page.</p>@endforelse
            @if (!empty($page['heading_outline']))<details class="mb-2"><summary>Heading outline (up to 40 headings)</summary><ul>@foreach ($page['heading_outline'] as $heading)<li><strong>H{{ $heading['level'] }}:</strong> {{ $heading['text'] ?: '(empty heading)' }}</li>@endforeach</ul></details>@endif
            @if (!empty($page['social']))<details class="mb-2"><summary>Shared-link preview tags</summary><dl>@foreach ($page['social'] as $property => $value)<dt>{{ $property }}</dt><dd>{{ $value ?: 'Not found' }}</dd>@endforeach</dl></details>@endif
            @if ($page['links'])
                <details><summary>{{ count($page['links']) }} discovered links (up to 500 collected; first {{ ($isDownload ?? false) ? 500 : 20 }} shown)</summary><ul>@foreach (array_slice($page['links'], 0, ($isDownload ?? false) ? 500 : 20) as $link)<li style="overflow-wrap:anywhere">{{ $link }}</li>@endforeach</ul></details>
            @endif
        </article>
    @endforeach
    <p class="small">Learn more: <a href="https://developers.google.com/search/docs/appearance/title-link">Page titles</a> &middot; <a href="https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag">Indexing rules</a>.</p>
</section>
