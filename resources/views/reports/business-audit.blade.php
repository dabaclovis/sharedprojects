<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Website audit report</title>
    @livewireStyles
    @include('reports.styles')
</head>
<body>
<main class="report-shell">
    @include('reports.toolbar', [
        'onlineReport' => true,
        'backUrl' => route(auth()->user()?->role === 'admin' ? 'admins.website-audits' : 'pages.business'),
        'backLabel' => auth()->user()?->role === 'admin' ? 'Back to website audits' : 'Back to business services',
    ])
    <header class="report-header">
        <span class="report-eyebrow">{{ config('app.name') }} &middot; Client website review</span>
        <h1>Website audit report</h1>
        <p class="report-plan">{{ $order->website }}</p>
        <small>Reference {{ $order->reference }}<br>Checked {{ $order->audit_result['checked_at'] ?? '' }}</small>
    </header>
    <section class="report-panel" aria-labelledby="action-plan-heading"><h2 id="action-plan-heading">Your action plan</h2><div class="report-plan">{{ $order->deliverable }}</div></section>
    <section class="report-panel" aria-labelledby="findings-heading"><h2 id="findings-heading">Automated findings</h2><ul>@forelse ($order->audit_result['issues'] ?? [] as $issue)<li><strong>{{ $issue['priority'] }}:</strong> {{ $issue['message'] }}</li>@empty<li>No issues were flagged by these automated checks.</li>@endforelse</ul></section>
    <section class="report-panel" aria-labelledby="scope-heading"><h2 id="scope-heading">Review scope</h2><p>This report reviews one webpage as it appeared when checked. Automated checks do not execute JavaScript, verify every linked page, or guarantee accessibility, security, or search rankings. Recommendations require review in the context of your website and business.</p></section>
</main>
@livewireScripts
</body>
</html>
