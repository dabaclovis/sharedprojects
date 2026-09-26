# Revenue services

Two services are available under **Admin dashboard → Paid website audits / Sponsorships**. Customers request quotes at `/services/business`.

## Research and product choices

Research checked on September 26, 2026:

- [BrightLocal managed services](https://www.brightlocal.com/local-seo-services/) demonstrates selling website analysis and recommendations as a service. This implementation is deliberately smaller: one public HTML page, automated checks, and an admin-written action plan. It does not claim BrightLocal's broader local-search capabilities.
- [BuySellAds publisher services](https://www.buysellads.com/publishers) describes publisher monetization through sponsorship campaigns. This implementation sells directly managed homepage sponsor cards, without an ad network integration.
- [Google's outbound link guidance](https://developers.google.com/search/docs/crawling-indexing/qualify-outbound-links) recommends `rel="sponsored"` for paid placements. Sponsor cards include that attribute and a visible Sponsored label.
- [Stripe Payment Links](https://stripe.com/payments/payment-links) is one optional way for the owner to collect payments externally. No Stripe integration or account is configured by this change.

These sources support the business models, not guaranteed demand or income. Audit pricing should cover manual review time. Sponsorship pricing should reflect real audience reach and the agreed duration. The application does not invent audience statistics or set market prices: an admin enters each agreed quote.

## Setup

Run `php artisan migrate` when deploying the code. The migration adds `service_orders`; it does not alter existing article, user, or product records. The homepage queries this table, so migrate before serving the new code. No new packages, API keys, queue workers, or scheduler entries are required.

Use HTTPS and set the correct `APP_URL` in deployment so report links use the public host. Schedule entry uses UTC and is converted to the configured application timezone for storage. Keep that timezone consistent after deployment. Existing cURL/network access used by the free site inspector is also required for paid audit checks.

## Website audit delivery

1. The customer selects Website audit and submits contact details, one public URL, and a brief. This creates a new unpaid request; it does not send email or take payment.
2. Open `/admin/services/website-audits`. Agree scope, delivery time, price, and payment arrangements directly with the customer. Save the quote in the selected currency.
3. Collect payment through the owner's bank or chosen provider. Verify the full amount there, then record its transaction reference in the application.
4. Start the audit and generate page findings. The existing safe fetcher blocks private/special network addresses, limits request size and duration, and does not automatically follow redirects. The paid audit respects robots.txt. Ask for a new request with a corrected URL if the original page is inaccessible.
5. Write and save a practical action plan. Review the findings before completing the report. Completion requires generated findings and at least 50 characters of written recommendations.
6. Copy the private report link and send it through your normal customer communication channel. Links expire after 30 days; reopening the completed order generates a new link. The client can print or save the report as PDF. Completed report text is locked.

Report pages contain the website, action plan, findings, and order reference. They exclude customer email, payment references, and internal notes. Anyone with a valid signed link can access that report. Refunds revoke access, including through previously issued links. Links are marked noindex and reports are served with no-store cache headers.

## Sponsorship delivery

1. The customer selects Sponsorship and submits their brief.
2. Open `/admin/services/sponsorships`. Agree the exact copy, destination URL, start/end dates, and price. Multiple sponsors share the homepage; placements are not exclusive.
3. Save the quote, collect payment externally, and record the verified transaction reference.
4. Enter the approved sponsor name, headline, description, HTTPS destination, and UTC dates. Approve and schedule the placement.
5. The homepage shows paid placements only while `starts_at <= current time < ends_at`. No scheduled job is needed. HTML entered as sponsor copy is escaped. Expired campaigns remain in the admin history and are no longer displayed.

## Operations and accounting boundaries

- Only active admins can access these tools, including subsequent Livewire requests. Order identifiers and revision snapshots are locked; stale edits are rejected.
- Quotes store integer minor units (cents), with supported two-decimal currencies. Receipt totals are grouped by currency and exclude orders marked fully refunded. They are manual records before fees and taxes, not a payment-provider balance or accounting ledger.
- Quote amounts cannot be changed after payment. Duplicate payment/refund actions are rejected. No action here charges a card, transfers money, issues an invoice, emails a customer, or initiates a refund.
- To cancel unpaid work, use Cancel request. For paid work, issue a full refund externally first, then use Record full refund. This cancels the request and withdraws a sponsorship or report. Partial refunds and automated payment reconciliation are not implemented.
- Requests and payment activity are retained for operational history rather than permanently deleted. Notes remain admin-only. Admin edits append the actor, timestamp, action, status, quote, and payment reference to the order history.
- Public inquiry submission is validated and limited to three requests per IP per hour. Audit generation is limited to ten attempts per admin per ten minutes.

## Verification

`php artisan test --compact --filter=RevenueServicesTest`

Tests cover inquiry validation and throttling, active-admin authorization, quote/payment/refund transitions, exact money values, stale edits, audit generation and fetch failures, private signed report access, sponsor scheduling and expiration, output escaping, and currency-separated receipts.
