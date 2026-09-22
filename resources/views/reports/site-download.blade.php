<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Website report</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.min.css">
<style>body{font:16px/1.6 system-ui,sans-serif;color:#193344;background:#f3f7fa;max-width:1100px;margin:auto;padding:30px}h2,h3{overflow-wrap:anywhere}article{background:white;border:1px solid #cbd5e1;border-radius:14px;padding:24px;margin:20px 0}dt{font-weight:bold}dd{margin:0 0 12px;overflow-wrap:anywhere}.row{display:flex;gap:20px;flex-wrap:wrap}.age-result-card{padding:18px;background:#e0f2f1;border-radius:12px}.age-result-value{font-size:28px;font-weight:bold;margin:0}a{color:#1257a0}@media print{body{background:white;padding:0;font-size:11pt}details>ul{display:block}article{break-inside:avoid}}</style>
</head><body>@include('reports.site-results', ['isDownload' => true])
<script>
    let previouslyClosed = [];
    window.addEventListener('beforeprint', () => {
        previouslyClosed = [...document.querySelectorAll('.site-report details:not([open])')];
        previouslyClosed.forEach(section => section.open = true);
    });
    window.addEventListener('afterprint', () => {
        previouslyClosed.forEach(section => section.open = false);
        previouslyClosed = [];
    });
</script></body></html>
