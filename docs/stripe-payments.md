# Stripe recurring payments

The tenant billing page uses Stripe-hosted Checkout for automatically renewing monthly/yearly USD subscriptions. Card data stays with Stripe. A signed `invoice.paid` event records the payment and grants access through that invoice's paid period. Browser success/cancel redirects never activate a plan.

## Environment and rollout

Use the same Stripe sandbox/account for all configuration. Add these server-only values to `.env`; never commit their values:

```dotenv
STRIPE_SECRET=sk_test_your_sandbox_secret
STRIPE_WEBHOOK_SECRET=whsec_your_endpoint_secret
STRIPE_PORTAL_CONFIGURATION=bpc_your_cancellation_only_configuration
```

`STRIPE_SECRET` and `STRIPE_WEBHOOK_SECRET` are both required before Checkout is enabled. Configure `APP_URL` to the canonical HTTPS deployment URL; clear cached configuration after changes. The customer portal configuration is optional only when the account's default configuration meets the rules below.

From the project root, using the workspace PHP 8.4 runtime:

```powershell
php84 composer.phar install --no-interaction
php84 artisan migrate --force
php84 artisan config:cache
php84 artisan queue:restart
php84 artisan queue:work --tries=3 --timeout=120
```

Run the queue worker under the deployment's process supervisor. Run `php84 artisan schedule:run` every minute using the deployment scheduler. The hourly `billing:retry-stripe-invoices` command requeues completed Stripe payments whose invoice email has not been sent, covering a queue outage after payment commit. Unique jobs and payment-row locks suppress normal duplicate delivery. A shared persistent cache is required for multi-worker unique job locks; the queue connection must be durable in production. Configure mail and test delivery before launch.

These instructions include deployment mutations; no production migrations or charges were performed during implementation.

## Stripe dashboard setup

Use snapshot webhook events at an API version compatible with the pinned SDK (`stripe/stripe-php` 22.0.0, API `2026-09-30.endive`). Register:

```text
https://your-app.example/integrations/stripe/webhook
```

Enable these events:

- `checkout.session.completed`
- `checkout.session.expired`
- `invoice.paid`
- `invoice.payment_failed`
- `customer.subscription.updated`
- `customer.subscription.deleted`

The endpoint uses the unmodified request body and `Stripe-Signature`. Invalid signatures return 400; absent webhook configuration returns 503; ownership/contract mismatches return 422. Temporary processing errors return non-2xx so Stripe retries. Event IDs and invoice IDs are deduplicated in the database. Events without a local checkout attempt are acknowledged without granting access.

Configure the hosted customer portal to allow payment-method updates, invoice history and cancellation **at the end of the billing period**. Disable subscription plan changes, quantity changes and prorations. Set `STRIPE_PORTAL_CONFIGURATION` to this configuration's ID. No portal configuration is created remotely by this implementation. Prices are built from immutable local package snapshots; no manual Stripe Price-ID mapping is required.

## Local sandbox checks

Use sandbox credentials and a disposable database. Start the application, queue worker and scheduler, then forward events using the official Stripe CLI:

```powershell
stripe listen --events checkout.session.completed,checkout.session.expired,invoice.paid,invoice.payment_failed,customer.subscription.updated,customer.subscription.deleted --forward-to http://127.0.0.1:8000/integrations/stripe/webhook
```

Use the CLI's signing secret locally (it differs from a dashboard endpoint secret). Clear cached configuration after changing it. Sign in as a tenant administrator and complete a real sandbox Checkout with a Stripe test card. Verify monthly and yearly plans, a first-invoice coupon, replayed events, a failed renewal and recovery, scheduled cancellation, and invoice delivery. Generic CLI invoice fixtures lack this application's attempt metadata; exercise Checkout from this application. Stripe Test Clocks can exercise renewals after creating a clock-backed sandbox customer and associating its real ID with the disposable tenant before Checkout.

Automated tests mock Stripe's gateway and verify signatures using the SDK. They do not establish that a real Stripe account or webhook endpoint is configured. A sandbox charge remains a required rollout check.

## Browser contracts

All `/admin/billing` browser actions require authentication, billing permission `1.4` and an active selected tenant administered by the user. Platform administrators must explicitly select a tenant. Invoice ownership is checked before viewing or emailing.

| Action | Inputs | Outcome |
| --- | --- | --- |
| `GET /admin/billing` | None | Catalog, status, paid-through and payment history |
| `POST /admin/billing/stripe` | `package_id`, `billing_cycle` (`monthly`/`yearly`), optional `coupon_code` | Redirect to real hosted subscription Checkout |
| `GET /admin/billing/stripe/success` | None | Informational pending-confirmation message |
| `GET /admin/billing/stripe/cancel` | None | Return to billing; no activation or remote cancellation |
| `POST /admin/billing/stripe/portal` | None | Portal for the selected tenant's stored real Stripe customer |
| `POST /admin/billing/validate-coupon` | `code`, `package_id`, `billing_cycle` | Server-price preview; final validation occurs at Checkout |

The Stripe action prohibits card fields, client totals/currency, customer IDs and return URLs. Success/cancel/portal URLs are server-generated. Verify exact invoice view/send URLs with `php84 artisan route:list --path=billing`; email accepts an optional validated recipient. Legacy coupon previews using `amount` remain supported but never determine Checkout pricing.

## Billing rules and recovery

- Coupons discount the first invoice only. Renewals use the agreed full recurring amount, even if the catalog changes later. Historical local invoices use persisted snapshots.
- Paid periods are extended only by paid invoices. Failed renewals keep access only until the previous paid-through time. Cancellation removes eligibility when Stripe reports the subscription canceled; scheduled cancellation preserves paid access until period end.
- A late paid invoice for a retired subscription remains in payment history and receives normal invoice delivery; it cannot replace the current subscription or alter its paid access.
- Active Stripe subscriptions block new Checkout. Use the portal to manage them. Plan changes/proration, taxes, multi-currency, refunds/disputes and Stripe Connect are outside this implementation.
- Initial unpaid Checkout does not replace local trial access. Local trial/legacy tenants without a real Stripe subscription keep their prior access behavior. Operations routes reject expired/canceled real subscriptions; billing, support and identity/sign-out remain accessible. API details are documented in `ionic/api.md`.
- Checkout attempts are stored before remote creation and retried with the same snapshot and idempotency keys. An uncertain attempt remains reserved until Stripe confirms expiry/payment. An unpaid terminal subscription releases the reservation. This prevents duplicate subscriptions and limited-coupon overselling.
- A `creating` attempt beyond its expiry requires operator reconciliation. Search Stripe request logs and Checkout Sessions by `checkout_attempt_id` metadata. Confirm whether a remote session/subscription was created before changing local state. If found, replay its signed lifecycle events through Stripe. If definitively absent/expired, close the attempt in an audited transaction. Do not blindly clear real tenant IDs, reset coupon counts or create a second subscription. Automated periodic reconciliation is deferred.
- Dedicated `stripe_customer_id`/`stripe_subscription_id` fields establish real identity. Historical simulated `billing_*` IDs and payments remain unchanged; old fake IDs are never sent to Stripe. The legacy PayPal service remains simulated and is not offered on the new Stripe page.

Official references: [hosted subscription Checkout](https://docs.stripe.com/billing/subscriptions/build-subscriptions?payment-ui=checkout&ui=stripe-hosted), [webhooks](https://docs.stripe.com/webhooks), [customer portal](https://docs.stripe.com/customer-management), [testing](https://docs.stripe.com/testing).
