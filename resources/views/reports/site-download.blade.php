<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Website report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.min.css">
    @if ($onlineReport ?? false) @livewireStyles @endif
    @include('reports.styles')
</head>
<body>
<main class="report-shell">
    @include('reports.toolbar', [
        'backUrl' => route('pages.'.($report->mode === 'crawler' ? 'web-crawler' : 'seo-audit')),
        'backLabel' => $report->mode === 'crawler' ? 'Back to website crawler' : 'Back to SEO audit',
        'expandable' => true,
    ])
    <header class="report-header">
        <span class="report-eyebrow">Website insights</span>
        <h1>{{ $report->mode === 'crawler' ? 'Website crawl report' : 'SEO audit report' }}</h1>
        <p class="report-muted">Review the summary, prioritize the action plan, and expand individual pages for details.</p>
    </header>
    @if ($report->status === 'running')<p class="report-notice">This report is a snapshot of the results collected so far. Return to the audit tool to continue checking pages.</p>@endif
    @include('reports.site-results', ['isDownload' => true])
</main>
@if ($onlineReport ?? false) @livewireScripts @endif
<script>
(() => {
    const controller = new AbortController();
    let previouslyClosed = [];
    window.addEventListener('beforeprint', () => {
        previouslyClosed = [...document.querySelectorAll('.site-report details:not([open])')];
        previouslyClosed.forEach(section => section.open = true);
    }, { signal: controller.signal });
    window.addEventListener('afterprint', () => {
        previouslyClosed.forEach(section => section.open = false);
        previouslyClosed = [];
    }, { signal: controller.signal });
    document.addEventListener('livewire:navigating', () => controller.abort(), { once: true, signal: controller.signal });
})();
</script>
</body>
</html>
