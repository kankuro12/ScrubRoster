# Stripe Recurring Payments Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Replace simulated Stripe billing with hosted recurring subscriptions, confirmed renewal payments and tenant-owned customer portal access.

**Architecture:** The official Stripe PHP SDK sits behind `StripeGateway`. Checkout attempts capture immutable pricing and retry identities. Signed events reconcile authoritative Stripe resources into the existing tenant/payment/invoice domain.

**Tech Stack:** Laravel 13, PHP 8.4, stripe/stripe-php, Bootstrap 5, jQuery, Axios, PHPUnit.

**Spec:** [Approved design](design.md).

## Global constraints

- Automatically renewing monthly or yearly USD tenant subscriptions.
- Local coupons discount the first invoice only; subsequent invoices use the agreed undiscounted recurring price.
- Cancellation at the end of the paid period. A failed renewal cannot extend access beyond the last successfully paid period.
- No activation from the browser return URL; no raw card inputs handled by Laravel.
- Controller-action routes, dedicated domain services, transactions for multi-record writes, explicit tenant/admin authorization and audit entries.
- Preserve unrelated working-tree changes; never migrate the live database or charge a real card for automated checks.
- Use `php84 composer.phar` and `php84 artisan`; this host provides `.codex/php84/php84.cmd`.
- Reuse `layouts.app`, Bootstrap 5/jQuery/Axios CDNs, `tokens.css` and `components.css`. Native labels/buttons, accessible status, one h1 and 320px reflow.
- Any API contract change must be mirrored in `ionic/api.md`; operational API access middleware was added and documented during execution.

## Review focus

1. Historical fake `cus_`/`sub_` IDs: never reuse without dedicated real Stripe identity fields (Task 1/2).
2. A timeout after Stripe creates a session: retry the same attempt with identical idempotency keys and parameters (Task 2).
3. Delayed failure or cancellation events after recovery: reconcile current Stripe state and never regress paid-through time (Task 3).
4. A package/coupon edit after payment: invoices retain the agreed amount and discount, unaffected by catalog changes (Task 4).
5. A paid invoice arriving before Checkout completion: map through server-generated subscription metadata without requiring browser/session state (Task 3).

## File boundaries and interfaces

- `app/Services/Billing/StripeGateway.php`: `customer(Tenant $tenant): string`, `checkout(array $parameters, string $key): array`, `subscription(string $id): array`, `invoice(string $id): array`, `coupon(array $parameters, string $key): string`, `portal(string $customerId, string $returnUrl): string`; SDK configuration and API calls.
- `app/Services/Billing/StripeCheckoutService.php`: `start(Tenant $tenant, Package $package, string $cycle, ?string $couponCode, string $email): string`; returns a hosted Checkout URL using a durable attempt.
- `app/Services/Billing/StripeWebhookService.php`: `handle(array $event): void`; reconciles a verified event, deduplicates ledger writes and queues invoices.
- `app/Models/StripeCheckoutAttempt.php`: immutable local Checkout snapshot and reservation lifecycle.
- `app/Models/StripeWebhookEvent.php`: processed event ledger without full payload/card/customer data.
- `app/Jobs/SendTenantPaymentInvoice.php`: idempotent post-commit invoice delivery.
- `app/Http/Controllers/Web/StripeWebhookController.php`: public SDK signature verification; delegates verified arrays.
- `app/Http/Controllers/Web/TenantBillingController.php`: browser validation, authorization and return/portal actions; calls Checkout service.
- `app/Services/Billing/BillingService.php`: removes simulated Stripe path; coupon/PayPal logic stays in its existing domain.
- `app/Services/Billing/InvoiceService.php`: immutable Stripe invoice presentation snapshots and safe invoice delivery transaction.
- `app/Models/Tenant.php`, `TenantPayment.php`: real Stripe fields, lifecycle casts/relations and paid access behavior.
- Migration, `composer.json/lock`, `config/services.php`, `.env.example`, `bootstrap/app.php`, `routes/web.php`, billing Blade, focused tests, `docs/stripe-payments.md`, module status and notebook notes.

### Task 1: Persistence and gateway boundary

**Files:** new migration `database/migrations/2026_10_12_120000_add_stripe_recurring_billing.php`; models above; gateway; dependency/config files; `tests/Feature/StripeBillingTest.php`.

**Interfaces:** produces model fields and `StripeGateway` signatures used by Tasks 2/3/5.

- [x] Write failing migration/model tests: `test_real_stripe_ids_are_distinct_from_simulated_history`, `test_invoice_id_is_unique`, `test_stripe_attempt_casts_and_relations`. Assert nullable unique `tenants.stripe_customer_id`/`stripe_subscription_id`, nullable `stripe_status`, boolean `stripe_cancel_at_period_end`, nullable real invoice/subscription/attempt references on tenant payments.
- [x] Run `php84 artisan test --filter=StripeBillingTest` and confirm expected missing-schema failures.
- [x] Install the current compatible stable official SDK with `php84 composer.phar require stripe/stripe-php`; preserve the resolved version in `composer.lock`. Configure secret, webhook signing secret, USD currency and optional portal configuration.
- [x] Create Checkout-attempt schema: UUID primary key; tenant/package/coupon foreign keys; cycle; decimal base/discount/total; currency; status; unique nullable Stripe session ID; expires_at and timestamps. Event ledger: Stripe event string primary key, event type and processed timestamp. Add fills, casts and relations.
- [x] Implement SDK gateway methods with stable creation keys, bounded API timeouts and response arrays. Never use legacy `billing_customer_id` to infer real identity. Customers are keyed by tenant; create Stripe metadata with tenant ID.
- [x] Run model/gateway tests, including missing-secret behavior and existing fake-ID fixtures; commit only task files.

### Task 2: Hosted recurring Checkout and safe coupons

**Files:** `StripeCheckoutService.php`, `StripeBillingTest.php`; consumes Task 1.

**Interfaces:** produces `start(Tenant, Package, string, ?string, string): string` and authoritative subscription metadata (`tenant_id`, `package_id`, `checkout_attempt_id`, `billing_cycle`).

- [x] Write failing tests: `test_checkout_is_recurring_and_does_not_activate`, `test_checkout_uses_server_price`, `test_checkout_retries_same_attempt`, `test_checkout_blocks_second_live_subscription`, `test_checkout_rejects_inactive_package_and_invalid_coupon`, `test_limited_coupon_counts_open_reservations`, `test_old_fake_customer_is_not_reused`. Assert `mode=subscription`, interval month/year, quantity 1, USD, expected catalog cents, and unchanged tenant access/payment completion before webhook.
- [x] Run the targeted tests and confirm failure before implementation.
- [x] Lock tenant and coupon rows inside a transaction. Validate package, cycle, employee quota and coupon. Count redemptions and pending unexpired attempts. Persist a 30-minute attempt and snapshots; reuse matching retryable/open attempts. Block a conflicting attempt or live subscription.
- [x] Create/reuse the dedicated real customer and a `duration=once` Stripe discount from the immutable snapshot. Create a session with recurring undiscounted price_data, server metadata, fixed named success/cancel URLs and the same attempt-based idempotency key on retries. Keep remote calls outside long database transactions.
- [x] Persist session ID/URL after success. Retain an uncertain failed attempt for safe retry; terminal gateway errors close it. Initial coupon redemption and plan activation stay pending.
- [x] Run Checkout tests, covering timeout after remote creation and package/coupon edits between retries; commit only task files.

### Task 3: Signed lifecycle events and renewal ledger

**Files:** webhook service/controller, route/bootstrap, tenant access logic, `StripeWebhookTest.php`; consumes Tasks 1/2.

**Interfaces:** `handle(array $verifiedEvent): void`; paid payments carry `stripe_invoice_id`, real subscription and attempt IDs plus immutable invoice metadata.

- [x] Write failing tests for raw-body signatures, missing/invalid configuration, duplicate event and invoice, invoice-before-checkout, unrelated customer/subscription rejection, unhandled event acknowledgement, failed renewal/recovery, scheduled cancellation/end, monthly/yearly paid periods, zero-total invoice and delayed stale events.
- [x] Run `php84 artisan test --filter=StripeWebhookTest` and confirm expected failures.
- [x] Register `POST /integrations/stripe/webhook` using a controller action, outside browser tenant resolution, with only that path exempt from CSRF. SDK signature verification receives `$request->getContent()`, `Stripe-Signature`, and webhook secret. Invalid signatures return 400, missing configuration 503, retryable errors non-2xx.
- [x] Handle exactly `checkout.session.completed/expired`, `invoice.paid/payment_failed`, `customer.subscription.updated/deleted`. Retrieve authoritative current resources; invoice subscription identity supports the SDK API version's current parent structure. Cross-check tenant/attempt metadata and known real customer ownership before any mutation.
- [x] Transactionally lock tenant, deduplicate processed event/invoice, record a paid invoice once, increment initial coupon redemption once, update package and paid-through only from paid invoice line period, and audit with explicit tenant ID. Support delivery before Checkout-completed using subscription metadata.
- [x] Keep initial unpaid attempts from replacing local trial access. Failed renewals cannot extend paid-through. Scheduled cancellation preserves already paid access; final cancellation removes eligibility. Recovered current Stripe status wins over stale events; retired subscription events cannot overwrite a new subscription.
- [x] Queue invoice delivery after commit. Retry processing failures without incorrectly marking the event processed. Run webhook tests and commit task files.

### Task 4: Stable invoices and reliable delivery

**Files:** invoice job/service, `StripeInvoiceTest.php`, TenantPayment; consumes Task 3.

**Interfaces:** `SendTenantPaymentInvoice($paymentId)` with queue retry behavior; existing `InvoiceService::formatInvoiceData(TenantPayment): array` uses Stripe snapshots when present.

- [x] Write failing tests: `test_invoice_survives_price_and_coupon_changes`, `test_invoice_job_does_not_resend_sent_invoice`, `test_email_failure_does_not_undo_paid_invoice`. Assert base/discount/total agree with stored Stripe invoice, and ledger entries remain unique on a retry.
- [x] Run tests and confirm expected failures.
- [x] Preserve first invoice base/discount snapshots and renewal actual invoice values; do not consult current coupon validity for historical billing. Skip already sent invoices using existing invoice sent fields and a transaction lock. Let failed jobs retry after payment commit.
- [x] Run invoice tests and commit task files.

### Task 5: Tenant-admin browser flow and customer portal

**Files:** billing controller/Blade, routes, `StripeBillingTest.php`; consumes Tasks 2/3.

**Interfaces:** existing `billing.stripe` now delegates to Checkout; new named `billing.stripe.success/cancel/portal` actions. Portal uses only authenticated tenant's real customer ID and fixed billing return URL.

- [x] Write failing tests for tenant admin-only mutation, tenant context mismatch, platform admin without selected tenant, portal customer ownership, return URLs that never activate, no raw card fields in HTML, and cross-tenant invoice view/send rejection.
- [x] Run route/authorization tests and confirm failures.
- [x] Add explicit permission middleware and tenant administrator/context ownership checks. Only active package and monthly/yearly/coupon selections reach Checkout; reject browser-supplied customer IDs/totals/return URLs. Remove the simulated Stripe service method and obsolete test expectation.
- [x] Replace card inputs with recurring amount/terms and native Continue to Stripe button using existing theme classes. Display pending, recovery, renewal and scheduled cancellation status. Existing subscribers use a Manage subscription portal button. Catalog plan changes are explicitly unavailable during a live subscription.
- [x] Use jQuery/Axios for any browser behavior; compile Blade and inspect 320px and desktop. Run billing and ownership tests; commit task files.

### Task 6: Rollout docs, notebook results and final verification

**Files:** `docs/stripe-payments.md`, work module docs, notebook note; relevant existing billing regression tests.

- [x] Replace the legacy fake Stripe test with a gateway-mocked hosted-checkout assertion; retain coupon, PayPal and Google-sync regression coverage.
- [x] Write exact secret/configuration/migration/queue commands, portal cancellation-only configuration, snapshot webhook event list and local Stripe CLI forwarding command. Explain historical fake payments and pending confirmation. Document all added web route inputs/outcomes.
- [x] Run `php84 artisan test --filter='Stripe|SaaSTenantManagerBillingAndSyncTest|MultiTenantTest|AuthTest'`, then full suite if targeted checks pass. Run Pint on changed PHP files, `php84 artisan view:cache` and `git diff --check`.
- [x] Review changes against approved design and failure cases, fix any material issue and rerun only affected checks. Use the requesting-code-review workflow for whole-change review.
- [x] Verify notebook sources/notes, save the implemented architecture and setup guide as new notes, update module `current.md` and root build-plan status with actual test results and remaining sandbox prerequisites.
- [x] Report automated verification separately from real Stripe validation. If no sandbox secrets are configured, do not claim an end-to-end Stripe charge was tested. Production deployment requires explicit credentials and environment setup.

## Execution choice

The user approved this plan and selected implementation in this chat on 2026-10-12. All tasks are complete. See execution-log.md for rulings and current.md for verification and remaining sandbox prerequisites.
