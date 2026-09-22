@php
    $data = $report->data;
    $pages = $data['pages'];
    $issues = collect($pages)->flatMap(fn ($page) => $page['issues']);
    $summary = app(\App\Services\SiteAudit\ReportSummary::class)->build($data);
@endphp
<style>
    .site-report .report-accordion { border: 1px solid #d8e5eb; border-radius: 14px; background: #fff; margin: 14px 0; overflow: hidden; box-shadow: 0 3px 12px rgba(25, 51, 68, .05); }
    .site-report .report-accordion > summary { display: flex; align-items: center; gap: 12px; padding: 16px 20px; cursor: pointer; list-style: none; font-weight: 700; color: #193344; }
    .site-report .report-accordion > summary::-webkit-details-marker { display: none; }
    .site-report .report-accordion > summary:hover, .site-report .report-accordion > summary:focus-visible { background: #eef9f8; }
    .site-report .report-accordion > summary:focus-visible { outline: 3px solid #087e79; outline-offset: -3px; }
    .site-report .report-accordion > summary .report-icon { display: inline-grid; place-items: center; flex: 0 0 36px; width: 36px; height: 36px; border-radius: 10px; background: #dff3ef; color: #087e79; }
    .site-report .report-accordion > summary .report-chevron { margin-left: auto; color: #65808c; transition: transform .2s; }
    .site-report .report-accordion[open] > summary .report-chevron { transform: rotate(180deg); }
    .site-report .report-accordion-body { padding: 4px 20px 20px; border-top: 1px solid #edf2f5; }
    .site-report .report-accordion--nested { box-shadow: none; margin: 9px 0; }
    .site-report .report-accordion--nested > summary { padding: 11px 14px; font-weight: 500; }
    .site-report .report-accordion--nested > summary .report-icon { flex-basis: 28px; width: 28px; height: 28px; border-radius: 8px; }
    .site-report .report-accordion--nested .report-accordion-body { padding: 12px 16px; }
    .site-report .report-url { overflow-wrap: anywhere; min-width: 0; }
    @media print { .site-report .report-accordion { box-shadow: none; break-inside: avoid; } .site-report .report-accordion > summary { padding: 8px 12px; } }
</style>
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
        <details class="report-accordion" open><summary><span class="report-icon"><i class="fa-solid fa-list-check" aria-hidden="true"></i></span><span>Your action plan <small class="w3-text-grey">({{ count($summary['actions']) }})</small></span><i class="fa-solid fa-chevron-down report-chevron" aria-hidden="true"></i></summary><div class="report-accordion-body">
        <p>Start at the top. Each item shows the pages affected so you can make targeted changes.</p>
        @foreach (array_slice($summary['actions'], 0, ($isDownload ?? false) ? count($summary['actions']) : 12) as $action)
            <details class="report-accordion report-accordion--nested"><summary><span class="report-icon"><i class="fa-solid {{ $action['priority'] === 'Fix first' ? 'fa-circle-exclamation' : 'fa-lightbulb' }}" aria-hidden="true"></i></span><span class="report-url"><strong>{{ $action['priority'] }}</strong> &middot; {{ count($action['urls']) }} {{ count($action['urls']) === 1 ? 'page' : 'pages' }} &middot; {{ $action['message'] }}</span><i class="fa-solid fa-chevron-down report-chevron" aria-hidden="true"></i></summary><div class="report-accordion-body"><ul>@foreach ($action['urls'] as $affected)<li style="overflow-wrap:anywhere">{{ $affected }}</li>@endforeach</ul></div></details>
        @endforeach
        @if (count($summary['actions']) > 12 && !($isDownload ?? false))<p class="small">Showing the first 12 action groups. Download the report for the full list.</p>@endif
        </div></details>
    @endif
    @if ($summary['broken'])
        <details class="report-accordion"><summary><span class="report-icon"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></span><span>Failed pages and where they are linked <small class="w3-text-grey">({{ count($summary['broken']) }})</small></span><i class="fa-solid fa-chevron-down report-chevron" aria-hidden="true"></i></summary><div class="report-accordion-body">
        @foreach ($summary['broken'] as $broken)
            <div class="alert alert-warning"><strong style="overflow-wrap:anywhere">{{ $broken['url'] }}</strong> &middot; HTTP {{ $broken['status'] ?: 'unavailable' }}
                @if ($broken['sources'])<p class="mb-1">Review links on these pages:</p><ul>@foreach ($broken['sources'] as $source)<li style="overflow-wrap:anywhere">{{ $source }}</li>@endforeach</ul>@else<p class="mb-0">No linking page was found in this sample. This URL may be the start page or come from a sitemap.</p>@endif
            </div>
        @endforeach
        </div></details>
    @endif
    @if ($summary['blocked'])<details class="report-accordion"><summary><span class="report-icon"><i class="fa-solid fa-robot" aria-hidden="true"></i></span><span>URLs skipped because of robots.txt</span><i class="fa-solid fa-chevron-down report-chevron" aria-hidden="true"></i></summary><div class="report-accordion-body"><ul>@foreach ($summary['blocked'] as $blocked)<li style="overflow-wrap:anywhere">{{ $blocked }}</li>@endforeach</ul></div></details>@endif
    <details class="report-accordion"><summary><span class="report-icon"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span><span>What to do next</span><i class="fa-solid fa-chevron-down report-chevron" aria-hidden="true"></i></summary><div class="report-accordion-body">
    @if (count($pages))
        <ol>
            <li>Start with “Fix first” findings. Restore failed pages and add missing titles where needed.</li>
            <li>Review indexing rules. Some pages are meant to stay out of search.</li>
            <li>Improve descriptions, headings, and image text where the report points out a gap.</li>
            <li>Run another check after your changes. Use Search Console to check actual search indexing and performance.</li>
        </ol>
    @else <p>No page results yet. Check the crawl notes below for progress or the reason the check stopped.</p>@endif
    </div></details>
    <details class="report-accordion"><summary><span class="report-icon"><i class="fa-solid fa-spider" aria-hidden="true"></i></span><span>Crawl notes <small class="w3-text-grey">({{ count($data['notes']) }})</small></span><i class="fa-solid fa-chevron-down report-chevron" aria-hidden="true"></i></summary><div class="report-accordion-body"><ul>@foreach ($data['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></div></details>
    <p class="small text-muted">This report checks fetched HTML, not rendered pages, rankings, backlinks, full accessibility, or Core Web Vitals. A finding is a prompt to review the page, not proof of a ranking problem. External links are listed but are not fetched. Structured data checks cover JSON syntax, not schema rules or rich-result eligibility.</p>
    @foreach ($pages as $page)
        <details class="report-accordion page-report"><summary><span class="report-icon"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span><span class="report-url">{{ $page['url'] }}<small class="d-block w3-text-grey">HTTP {{ $page['status'] ?: 'unavailable' }} &middot; {{ count($page['issues']) }} {{ count($page['issues']) === 1 ? 'finding' : 'findings' }}</small></span><i class="fa-solid fa-chevron-down report-chevron" aria-hidden="true"></i></summary><div class="report-accordion-body">
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
        </div></details>
    @endforeach
    <p class="small">Learn more: <a href="https://developers.google.com/search/docs/appearance/title-link">Page titles</a> &middot; <a href="https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag">Indexing rules</a>.</p>
</section>
