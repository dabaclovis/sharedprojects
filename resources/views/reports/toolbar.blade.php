<nav class="report-toolbar" aria-label="Report actions">
    <a @if ($onlineReport ?? false) wire:navigate @endif class="report-button" href="{{ $backUrl }}"><span aria-hidden="true">&larr;</span> {{ $backLabel }}</a>
    @if ($expandable ?? false)
        <button type="button" class="report-button" onclick="document.querySelectorAll('.site-report details').forEach(section => section.open = true)">Expand all</button>
        <button type="button" class="report-button" onclick="document.querySelectorAll('.site-report details').forEach(section => section.open = false)">Collapse all</button>
    @endif
    <button type="button" class="report-button report-button-primary" onclick="window.print()">Print / Save as PDF</button>
</nav>
