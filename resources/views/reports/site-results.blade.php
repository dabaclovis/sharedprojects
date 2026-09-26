@php
    $data = $report->data;
    $pages = $data['pages'];
    $issues = collect($pages)->flatMap(fn ($page) => $page['issues']);
    $summary = app(\App\Services\SiteAudit\ReportSummary::class)->build($data);
@endphp
<style>
    .site-report { min-width: 0; }
    .site-report .report-overview { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 16px; margin: 24px 0; }
    .site-report .report-overview-copy { flex: 1 1 260px; min-width: 0; }
    .site-report .report-overview h2 { margin: 0 0 8px; color: #193344; font-size: clamp(1.2rem, 2.5vw, 1.65rem); line-height: 1.4; overflow-wrap: anywhere; }
    .site-report .report-meta { margin: 0; color: #5b7080; font-size: .85rem; }
    .site-report .report-status { display: inline-flex; align-items: center; gap: 8px; flex-shrink: 0; padding: 7px 12px; border-radius: 999px; background: #e7eef5; color: #344d63; font-size: .8rem; font-weight: 700; }
    .site-report .report-status::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .site-report .report-status-failed { background: #fde9e7; color: #9e3029; }
    .site-report .report-status-complete { background: #dcf4e9; color: #216347; }
    .site-report .report-status-running { background: #e6efff; color: #254f99; }
    .site-report .report-metrics { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin: 24px 0; }
    .site-report .report-metric { box-sizing: border-box; min-width: 0; width: 100%; max-width: none; padding: 22px; border: 1px solid #dce6ec; border-top: 3px solid var(--metric-accent); border-radius: 14px; background: linear-gradient(145deg, #fff 45%, var(--metric-tint)); box-shadow: 0 5px 18px rgba(25, 51, 68, .04); }
    .site-report .report-metric-teal { --metric-accent: #0f766e; --metric-tint: #effaf7; }
    .site-report .report-metric-amber { --metric-accent: #a1510c; --metric-tint: #fff7e8; }
    .site-report .report-metric-blue { --metric-accent: #2855c7; --metric-tint: #f0f5ff; }
    .site-report .report-metric-purple { --metric-accent: #7b3bb5; --metric-tint: #f8f1ff; }
    .site-report .report-metric-value { margin: 0 0 8px; color: var(--metric-accent); font-size: 2rem; font-weight: 750; line-height: 1.2; }
    .site-report .report-metric-label { margin: 0; color: #526576; font-size: .875rem; font-weight: 600; line-height: 1.5; overflow-wrap: normal; word-break: normal; }
    .site-report .report-failure { padding: 18px 22px; border: 1px solid #f0d2cc; border-radius: 12px; background: #fff5f2; color: #833d31; }
    .site-report .report-failure p { margin: 8px 0 0; overflow-wrap: anywhere; }
    .site-report .report-summary-note { padding: 14px 18px; border-radius: 10px; background: #eaf1f6; color: #4a6273; font-size: .875rem; line-height: 1.6; }
    @media (max-width: 850px) { .site-report .report-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 420px) { .site-report .report-metrics { grid-template-columns: 1fr; gap: 12px; } .site-report .report-metric { padding: 18px 20px; } }
    @media print { .site-report .report-metrics { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; } .site-report .report-metric { padding: 12px; box-shadow: none; break-inside: avoid; } }
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
    <header class="report-overview">
        <div class="report-overview-copy"><h2>Report for {{ $report->url }}</h2><p class="report-meta">Started {{ $report->created_at->format('M j, Y H:i') }} UTC</p></div>
        <span class="report-status report-status-{{ in_array($report->status, ['failed', 'complete', 'running', 'stopped']) ? $report->status : 'unknown' }}">{{ $report->status === 'stopped' ? 'Paused' : ucfirst($report->status) }}</span>
    </header>
    @if ($report->status === 'failed')
        <div class="report-failure" role="status"><strong>The website check could not finish</strong><p>{{ count($pages) === 0 ? 'No pages were checked. The counts below do not mean the website is free of issues.' : 'These are partial results. Some pages could not be checked.' }}</p>@if (!empty($data['notes']))<p>{{ \Illuminate\Support\Arr::last($data['notes']) }}</p>@endif</div>
    @endif
    <div class="report-metrics" aria-label="Report summary">
        @foreach (['Pages checked' => count($pages), 'Fix first' => $issues->where('priority', 'Fix first')->count(), 'Other findings' => $issues->where('priority', '!=', 'Fix first')->count(), 'Blocked by robots.txt' => $data['skipped']] as $label => $value)
            <div class="report-metric report-metric-{{ ['teal', 'amber', 'blue', 'purple'][$loop->index] }}"><p class="report-metric-value">{{ number_format($value) }}</p><p class="report-metric-label">{{ $label }}</p></div>
        @endforeach
    </div>
    <p class="report-summary-note">{{ $summary['pending'] }} queued URLs remain unchecked. @if ($summary['average_ms'] !== null) Average successful fetch: {{ $summary['average_ms'] }} ms.@endif This is a limited sample of the site.</p>
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
