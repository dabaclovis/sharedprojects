# SEO audits and website crawler

Public routes: `/services/seo-audit` and `/services/web-crawler`.

Each report is saved in `site_reports`. Results include checked URLs, response status and time, page details, findings, and recommendations. Raw page HTML is not saved. Crawl state and robots rules are stored while the report exists. The report contains no visitor IP; an IP hash is used temporarily in the rate-limit cache. Database copies are retained until an administrator removes them.

The browser runs one crawl step every three seconds while the page is open. Longer crawl delays are respected up to 60 seconds. Closing the page pauses progress; returning in the same session resumes it. No queue worker is needed. Pause keeps partial results, and Resume continues them. HTTP 429 pauses the report for at least a minute and honors a longer Retry-After header; resuming retries that page without losing completed results. Starting a new report stops the previous one. The ten latest reports in the same session can be reopened. There is no public report directory or anonymous lookup by ID.

Downloads contain current results in standalone HTML, JSON, or spreadsheet CSV. CSV cells are protected against common spreadsheet formula injection. Visitors can print the HTML report to PDF. A new browser session cannot recover older reports; visitors should download a copy before leaving.

Reports include a prioritized action plan, failed pages with their referring pages, blocked URLs, a search preview, heading outlines, shared-link tags, link counts, conflicting metadata, canonical URL checks, mixed HTTP resources, and JSON-LD syntax checks. The live report limits long lists for readability; HTML and JSON exports include the complete collected lists. Existing saved reports remain readable.

## Boundaries

- SEO: one page. Crawler: up to 50 pages, a choice of one to five link levels, and an optional starting-path restriction. Sitemap seeds start at depth zero. The discovery queue holds at most 300 pending URLs; at most 2,000 distinct eligible URLs are tracked. Queued URLs are deduplicated using an indexed lookup. Link sources are capped at 1,000 destination URLs and 20 sources each.
- Same origin only. Redirects are reported; crawler mode may visit a same-origin destination as another page. For redirects on robots.txt, enter the final site address.
- robots.txt allow/disallow groups, wildcards, and encoded paths are checked. Delays use the largest declared value conservatively. Unreadable robots rules stop the run; 404 and 410 mean no rules supplied. Nofollow, sponsored, and UGC links are reported but not scheduled from those links.
- Up to five sitemap files are checked, including nested sitemap indexes. Up to 100 same-origin URLs per sitemap can be considered for the bounded queue. Sitemap loops are deduplicated. Gzip sitemap files are not expanded.
- Five report starts per IP per ten minutes. Each response has a 12-second timeout and a 2 MB decoded-body limit.
- Only public IPv4 addresses on normal HTTP/HTTPS ports. DNS results are validated and the connection is pinned to the checked address. Private, reserved, and special-use addresses are blocked. Redirects are never followed automatically. TLS verification remains enabled.
- No JavaScript rendering, external-link checking, ranking data, backlink data, full accessibility testing, or browser performance scoring. JSON-LD syntax is checked, but schema requirements and rich-result eligibility are not validated.
- Findings are review suggestions. The absence of noindex does not establish that a page is indexed.

Requirements: PHP cURL, DOM, SimpleXML, outbound DNS/HTTP/HTTPS, and the normal Laravel database/cache/session setup.

Verification: `php artisan test --compact tests/Feature/SiteInspectorTest.php tests/Feature/SiteAuditAdvancedTest.php`.
