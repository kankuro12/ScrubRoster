# Execution ledger — plan: work/stripe-payment/implementation-plan.md

2026-10-12: User approved written design, plan and native execution in this chat.

Pre-flight: Task 1 supplies gateway/model interfaces to Tasks 2/3; Task 2 supplies immutable attempts to Task 3; Task 3 supplies invoice snapshots to Task 4; Task 5 delegates to Tasks 2/3. Interfaces match.

Ruling: Execute in the current non-main feature branch (`codex/header-logo-visibility`), preserving pre-existing CSS/header/foundation edits — user selected implementation here; creating/moving to another checkout would complicate delivery without improving the scoped change.

Ruling: Keep the progress ledger in the module folder instead of Unix-only helper scripts — Windows PowerShell is the runtime, and the durable module record must survive compaction.

Baseline: SaaSTenantManagerBillingAndSyncTest, 7 passed / 1 failed. Existing homepage assertion expects obsolete `Field-Ops SaaS` text; the actual homepage has changed. No Stripe code changed at baseline.

Notebook research completed in the background and was saved as a note. Generated recommendations are reference only; the approved design overrides its suggested grace period, legacy-data rewrite and invented method/field examples.

Task 1: in progress.

Tasks 1/2: persistence and hosted checkout implemented; StripeBillingTest 9 passed, 35 assertions. SDK v22.0.0, API 2026-09-30.endive. Ruling: uncertain attempts remain reserved until verified expiry or payment, even after local TTL, preventing duplicate subscriptions/coupon overselling. Return routes moved forward from Task 5 to satisfy Task 2's fixed URL dependency. Composer optimized autoload stalled; completed nonoptimized dump with configuration restored. Task 3 in progress.

Tasks 3/4: webhook 11 tests passed, 49 assertions; invoice 3 tests passed, 12 assertions. Ruling: fetch authoritative subscription under tenant lock to serialize lifecycle updates; invoice retries lock payment rows and resolve admin email outside stale tenant scopes. Task 5 in progress.

Ruling: add EnsureStripeSubscription middleware to web/API operations because isSubscriptionActive had no consumers; otherwise the approved paid-through/cancellation policy would be presentation only. Billing/support/identity/logout remain accessible; legacy tenants are unaffected. Mirrored API 403 behavior in ionic/api.md. Stripe page presents real hosted checkout; simulated PayPal backend retained for legacy callers but is not presented as real payment checkout. UI design uses existing navy/orange tokens scoped to billing. Task 5 tests cover authorization, owned portal, protected invoices and no card inputs.

Ruling: refresh the obsolete homepage assertion to its current Scheduling & Attendance title, preserving the homepage — baseline failure predated this module. SaaSTenantManagerBillingAndSyncTest then passed 8 tests / 47 assertions.

Tasks 1/2: complete (33dbc10). Tasks 3/4: complete (423c70f plus delivery recovery). Task 5: complete. Task 6: complete.

Delivery recovery RED: missing billing:retry-stripe-invoices command. GREEN: unique invoice job plus hourly ledger-based recovery; Stripe suite 35 passed / 133 assertions. Terminal-unpaid reservation RED: awaiting_payment instead of failed. GREEN: release only when no paid ledger/latest paid invoice exists.

Final reviewer found PayPal and platform-management routes could restore unpaid access. RED regression tests reproduced direct simulated payment and platform access. GREEN: real Stripe identities block simulated PayPal; all platform-management routes require a platform admin with no tenant_id. Ruling: restrict platform management, including for currently paid tenants — preventing prepaid trial grants is necessary to enforce invoice-confirmed access.

Ruling: use current locking reads for coupon reservation rows under the coupon lock, align webhook tenant/coupon/attempt lock order and allow 3 database deadlock retries — MariaDB REPEATABLE READ plain counts can omit newly committed reservations after waiting for a coupon lock. No live concurrent MariaDB test was run; existing reservation tests and source-based review verify the change within available test infrastructure.

Final review: approved after these corrections; no remaining concrete findings. Deferred plan changes/proration/tax/refunds/disputes/reconciliation, real sandbox account testing and exactly-once SMTP crash semantics remain outside the approved implementation scope.

Verification: Stripe-focused 38 passed / 140 assertions. Full suite 224 passed / 1 skipped / 866 assertions. Pint, view:cache, package:discover, Composer validation and git diff --check succeeded. Browser checked isolated SQLite fixture at 320px and 1440px, no page overflow; monthly/yearly modal terms verified. Preview files/server removed. Notebook implementation guide saved as note 09cecbc8-9df5-47c7-b11c-7efeff3ea263.

No production migration, real payment, push or deployment performed. Existing unrelated CSS/header/foundation work preserved. Branch retained in this checkout as requested by native execution; publishing/merging requires a separate user request.

Final accounting regression: a late paid invoice for a retired subscription was previously acknowledged without a payment ledger entry. RED: expected 2 payments, found 1. GREEN: record/deduplicate owned old paid invoices and queue delivery while grantAccess=false preserves the replacement subscription. The same reviewer confirmed this narrow fix. Test timestamp normalized to database second precision after an initial microsecond comparison failure. Final full suite: 225 passed / 1 skipped / 873 assertions (226 total). Pint --test and view:cache passed. Final verification notebook note: 45b29a6b-6a5b-4de2-af38-246756da4403. Only appended billing CSS blocks staged; pre-existing brand-size CSS/header/foundation edits left untouched.
