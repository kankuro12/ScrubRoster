# Stripe payment — current

Completed on 2026-10-12 after user approval of the recurring design and native implementation plan.

## Implemented

- Main branch integration (2026-10-15): retained real checkout, subscription middleware and invoice recovery alongside email verification and alert scheduling. InvoiceService uses `sendNow` inside the existing Stripe delivery job so delivery failures remain retryable and `invoice_sent_at` follows transport acceptance.
- Official Stripe PHP SDK 22.0.0 (major constrained to ^22.0); dedicated real customer/subscription identity leaves simulated history intact.
- Tenant-admin hosted monthly/yearly USD subscription Checkout, server prices, immutable durable attempts, stable idempotency keys and duplicate-subscription prevention.
- First-invoice-only coupons; serialized limited-use reservations and release after verified expiry/unpaid terminal subscriptions.
- Signed snapshot webhooks, authoritative lifecycle reconciliation, event/invoice deduplication, paid-invoice-only activation and renewal, last-paid-period access and scheduled cancellation.
- Stable historical invoice snapshots, queued duplicate-suppressed email delivery and hourly recovery from queue outages.
- Owned customer portal, protected invoices, no raw card fields, explicit recurring terms, loading/empty/recovery states and responsive Bootstrap billing UI.
- Web/API operations enforce paid access for real Stripe subscriptions. Billing, support and identity remain available; ionic/api.md records the shared API 403 contract.
- Platform-only tenant management prevents tenant admins granting unpaid trials. Real Stripe tenants cannot activate through the legacy simulated PayPal route.
- [Rollout guide](../../docs/stripe-payments.md), executed plan, execution ledger and NotebookLM notes saved.

## Verified

- Main branch integration (2026-10-15): full suite passed (225 passed, 1 skipped, 873 assertions), including invoice delivery/deduplication and subscription access tests. The command-local `php84` wrapper used installed PHP 8.5.11 with SQLite enabled; no production migrations or real gateway requests were run.
- Full suite: 225 passed, 1 skipped, 873 assertions (226 total tests).
- Stripe-focused suite: 39 tests included in the full passing suite; the prior focused run passed 38 tests / 140 assertions before the final 7-assertion late-invoice regression.
- Laravel Pint applied to changed PHP; Blade view cache, package discovery, Composer validation and git diff --check passed.
- Browser QA used an isolated in-memory SQLite fixture: one h1, 320px and 1440px reflow, monthly/yearly price switching and recurring checkout terms. Temporary preview/server removed; screenshots remain in storage/app/stripe-billing-{mobile,desktop}.png.
- Fresh read-only code review approved after fixing PayPal/platform-management access bypasses and MariaDB coupon snapshot locking. Concurrency behavior was reviewed against MariaDB semantics; a real parallel MariaDB race was not executed.
- Automated gateway mocks and signature verification do not replace a real Stripe sandbox charge.

## Rollout prerequisites

Configure server-side Stripe test credentials, signed webhook endpoint, cancellation-only portal, migrations, durable queue/shared cache, scheduler and mail as described in the rollout guide. Run a real sandbox Checkout/renewal/cancellation smoke test before production deployment. No production database migration, real charge, push or deployment was performed.

## Notebook research

[ScrubRoster — Stripe Payments Integration](https://notebook.google.com/notebook/fe744f09-0f44-4bbb-a442-867d0f985765) contains Laravel documentation, a sanitized codebase audit, verified official Stripe summaries, generated research, approved-design overrides and the implemented rollout guide. Direct Stripe URL imports produced entries with no indexed content in the inspected webhook source; the authored verified summary preserves usable references. Generated research completed in the background and was saved, but the approved design remains binding.
