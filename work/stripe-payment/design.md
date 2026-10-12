# Stripe recurring payments — reviewable design

Date: 2026-10-12. Status: written design approved by the user; implementation has not started.

Notebook: [ScrubRoster — Stripe Payments Integration](https://notebook.google.com/notebook/fe744f09-0f44-4bbb-a442-867d0f985765).

## Outcome and scope

Replace the existing simulated Stripe checkout with real, automatically renewing monthly or yearly USD tenant subscriptions. Retain the package catalog, tenant boundaries, payment history and local invoices. Tenant administrators select a package and complete Stripe-hosted Checkout; card details never enter ScrubRoster. Signed Stripe events confirm payment and maintain subscription state. Administrators can manage their payment method, view Stripe invoices and cancel through a hosted customer portal.

The user requested both a NotebookLM research project and a working implementation, then explicitly chose automatic renewal. Local coupons are proposed to discount the first invoice only; subsequent invoices charge the agreed undiscounted recurring price. Cancellation is proposed at the end of the paid period. A failed renewal cannot extend access beyond the last successfully paid period. These business rules are part of this design for review.

## Existing behavior and hazards

- `BillingService::processStripePayment` creates fake transaction/customer IDs, immediately activates the tenant, and never calls Stripe. Its invoice-generation code follows an unconditional return and is unreachable.
- `TenantBillingController::processStripe` accepts raw card number, expiry and CVC. These inputs must be removed from the controller and Blade form.
- Existing billing routes require authentication but need explicit billing permission and tenant administrator checks. Invoice view/send must verify ownership.
- `Tenant` already stores package, provider, customer/subscription identifiers and a paid-through timestamp. Existing Stripe-looking identifiers may be simulated; their shape cannot establish that they are real.
- Existing Stripe tests assert the simulated behavior and must be replaced with tests for actual gateway interactions and webhook-confirmed activation.

## Approach

Use the official `stripe/stripe-php` SDK through dedicated services. This fits the existing tenant/payment/invoice schema and allows explicit lifecycle handling. Cashier is a viable alternative, but adopting its billable-model and subscription tables would require a larger schema migration. A custom card form using Elements would add frontend payment handling without benefiting this hosted flow.

Service responsibilities:

- `StripeGateway`: configured SDK access for customers, Checkout, portal, subscriptions and invoices; no secrets or raw payloads in logs.
- `StripeCheckoutService`: authorize the tenant, obtain a server-side price/coupon snapshot, reserve a durable attempt, create/reuse customer and Checkout session with stable idempotency keys, and return the hosted URL.
- `StripeWebhookService`: verify identity, reconcile authoritative Stripe resources, record paid invoices once, update lifecycle state transactionally and log audits.
- A webhook controller verifies the raw request signature with the SDK before handing an event to the service. Browser controllers only validate inputs and delegate.

## Data and concurrency

Add dedicated nullable unique real Stripe customer/subscription identifiers to tenants so historical simulated IDs cannot accidentally be reused. Store Stripe lifecycle status and scheduled cancellation separately from local paid access. Keep `subscription_ends_at` as the last successfully paid-through time; do not extend it from an unpaid `subscription.updated` event.

Add `stripe_checkout_attempts`: UUID, tenant/package/coupon references, cycle, base/discount/total snapshots, currency, status, nullable unique session ID, expiry and timestamps. Generate an attempt before the remote call. Reuse the same snapshot and idempotency keys after a timeout. Serialize attempts using a tenant row lock so a tenant cannot accidentally create parallel subscriptions. Block new Checkout while a live Stripe subscription exists and direct the admin to the portal. Plan switching/proration is excluded from this first implementation; it must not be simulated by creating a second subscription.

Add nullable unique Stripe invoice IDs and subscription/attempt references to payment records. Add a durable processed-event ledger. Update model fillable attributes, casts and relationships with each migration.

Reserve limited-use coupons under a coupon row lock, counting unexpired pending attempts plus completed redemptions. Use a Stripe coupon with `duration=once` built from the server snapshot. Redeem locally only after the initial invoice is paid, exactly once. Expired Checkout attempts release reservations; uncertain remote API failures retain a retryable attempt rather than allowing another subscription. Zero-total initial invoices are allowed when Stripe reports the invoice paid and the subscription active.

## Webhook and access rules

Public endpoint: `POST /integrations/stripe/webhook`, outside tenant browser middleware, exempt only from CSRF, authenticated by the Stripe signature. Configure snapshot events with an API version compatible with the installed SDK. Reject invalid signatures with 400; return 503 for missing configuration and non-2xx for retryable processing failures. Acknowledge unhandled valid events without side effects.

Handled events: `checkout.session.completed`, `checkout.session.expired`, `invoice.paid`, `invoice.payment_failed`, `customer.subscription.updated`, `customer.subscription.deleted`.

Retrieve authoritative current subscriptions/invoices where needed so delayed delivery cannot overwrite newer lifecycle state. Cross-check customer, subscription, tenant and attempt metadata against local ownership; never select a tenant from session state or an unverified browser field. Support invoice delivery before Checkout completion using subscription metadata. Transactionally lock the tenant, deduplicate the event and invoice, apply package/paid-period changes, redeem the coupon and write audit records.

Only a paid invoice associated with an eligible subscription grants or extends paid access. Initial Checkout completion alone does not activate a package. Initial unpaid/incomplete subscriptions do not replace an existing local trial. Failed renewal records a recovery state and leaves the paid-through timestamp intact; access expires at that timestamp. Recovery through a paid invoice restores eligibility. Scheduled cancellation preserves paid access until the recorded period end; final cancellation removes eligibility. A stale failed-payment event must not undo a recovered subscription.

Notify/send local invoices after commit, with an idempotent queued delivery path so email failure cannot roll back payment or cause duplicate ledger entries. Invoice presentation uses immutable price/discount snapshots, not today's catalog or coupon validity.

## Browser behavior and authorization

Retain `layouts.app` and the existing Bootstrap/jQuery/Axios stack and theme tokens. Replace raw card fields with a clear recurring-price summary and a Continue to Stripe button. Show renewal terms, first-invoice coupon amount, pending confirmation, recovery and cancellation states. Provide an administrator-only Manage subscription portal action. Keep a visible accessible status badge and native form labels/buttons.

Success and cancellation return routes display status only and cannot activate the tenant. Users cannot pass customer IDs, totals, currency or arbitrary return URLs. Package IDs must refer to active catalog entries; quotas must permit the chosen package. Billing mutations and invoice access are scoped to the authenticated tenant admin. Platform admins require explicit tenant selection for tenant-specific billing operations.

## Configuration and rollout

Document `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, optional portal configuration and USD catalog currency in `.env.example`; configuration belongs in `config/services.php`. No keys are uploaded to NotebookLM or committed. Use a new real customer for historical simulated Stripe records. Apply schema changes without rewriting historical payments or claiming they were real charges.

Setup guide: install locked SDK dependency with `php84 composer.phar`, run migrations, configure test-mode keys, configure the customer portal for payment methods/invoices and end-of-period cancellation, register the webhook and event list, run a queue worker, and complete sandbox Checkout. Production requires live keys, a live webhook signing secret and a publicly accessible HTTPS endpoint.

No API route/contract change is planned. If API routes, validation or response shapes change during implementation, update `ionic/api.md` in the same change. PayPal, customer invoices for cleaning work, payouts, Connect, taxes, refunds/disputes automation and automatic plan switching are outside this Stripe subscription change. Record them explicitly as future work.

## Verification

Use isolated test databases and gateway mocks; do not charge real cards or migrate the live database during automated checks. Test hosted redirect with no pre-payment activation, inactive packages, tenant/admin authorization, forged totals and IDs, missing configuration, rejected signature, raw-body signing, duplicate event/invoice delivery, invoice-before-checkout delivery, replayed older lifecycle events, monthly/yearly renewal periods, zero-total first invoices, invalid/expired/limited coupons, concurrent-attempt prevention, API timeout retry keys, failed renewal/recovery, cancellation through period end, portal ownership, and invoice snapshots/delivery retries.

Run targeted billing tests, tenancy/auth regressions, Pint on changed PHP files, Blade compilation and `git diff --check`. Review billing at 320px and desktop with labels, one h1, keyboard focus and no overflow. Report a sandbox smoke test separately from automated mocked tests; real Stripe validation requires configured test credentials.

## Sources and research limits

- [Stripe hosted subscriptions](https://docs.stripe.com/billing/subscriptions/build-subscriptions.md?payment-ui=checkout&ui=stripe-hosted)
- [Stripe subscription webhooks](https://docs.stripe.com/billing/subscriptions/webhooks)
- [Stripe webhook verification and delivery](https://docs.stripe.com/webhooks)
- [Stripe idempotency](https://docs.stripe.com/api/idempotent_requests)
- [Stripe customer portal](https://docs.stripe.com/customer-management)
- [Checkout Session API](https://docs.stripe.com/api/checkout/sessions/create)
- [Stripe test environments](https://docs.stripe.com/testing)
- [Official Stripe PHP SDK](https://github.com/stripe/stripe-php)
- [Laravel Cashier](https://laravel.com/framework/docs/billing)

Official pages were checked on 2026-10-12. NotebookLM imported Laravel documentation and two authored sources: the codebase audit and verified Stripe summaries. Direct Stripe URL imports failed. NotebookLM's initial analysis request did not return within the allotted wait and was stopped; no generated report is represented as completed. This document is the reviewed-by-agent proposal, not a claim of implemented code or live Stripe testing.
