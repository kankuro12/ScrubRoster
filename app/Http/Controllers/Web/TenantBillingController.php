<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Services\Billing\BillingService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\StripeCheckoutService;
use App\Services\Billing\StripeGateway;
use App\Services\Billing\TenantBillingAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

class TenantBillingController extends Controller
{
    public function index(Request $request, TenantBillingAccess $access): View
    {
        $tenant = $access->tenant($request);

        $packages = Package::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price_monthly')
            ->get();

        $payments = TenantPayment::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with(['package:id,name'])
            ->latest()
            ->paginate(10);

        $activeEmployeesCount = $tenant->getActiveEmployeesCount();
        $maxEmployees = $tenant->getMaxEmployees();
        $quotaPercentage = $maxEmployees > 0 ? min(100, round(($activeEmployeesCount / $maxEmployees) * 100)) : 0;

        return view('pages.billing', [
            'tenant' => $tenant,
            'currentPackage' => $tenant->package,
            'packages' => $packages,
            'payments' => $payments,
            'activeEmployeesCount' => $activeEmployeesCount,
            'maxEmployees' => $maxEmployees,
            'quotaPercentage' => $quotaPercentage,
            'stripeConfigured' => filled(config('services.stripe.secret')) && filled(config('services.stripe.webhook_secret')),
        ]);
    }

    public function validateCoupon(Request $request, BillingService $billing): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'amount' => ['required_without:package_id', 'nullable', 'numeric', 'min:0'],
            'package_id' => ['nullable', 'exists:packages,id'],
            'billing_cycle' => ['required_with:package_id', 'nullable', 'in:monthly,yearly'],
        ]);

        $result = $request->filled('package_id')
            ? $billing->validatePackageCoupon(Package::findOrFail($request->input('package_id')), $request->input('billing_cycle'), $request->input('code'))
            : $billing->validateCoupon($request->input('code'), (float) $request->input('amount'));

        return response()->json($result);
    }

    public function stripeSuccess(): RedirectResponse
    {
        return redirect()->route('billing.index')->with('status', 'Stripe checkout submitted. Your plan activates after payment confirmation.');
    }

    public function stripeCancel(): RedirectResponse
    {
        return redirect()->route('billing.index')->with('status', 'Checkout was canceled. Your current plan is unchanged.');
    }

    public function processStripe(Request $request, TenantBillingAccess $access, StripeCheckoutService $checkout): RedirectResponse
    {
        $tenant = $access->tenant($request);

        $validated = $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'card_name' => ['prohibited'], 'card_number' => ['prohibited'],
            'card_expiry' => ['prohibited'], 'card_cvc' => ['prohibited'],
            'amount' => ['prohibited'], 'currency' => ['prohibited'],
            'customer_id' => ['prohibited'], 'return_url' => ['prohibited'],
        ]);

        $package = Package::findOrFail($validated['package_id']);

        try {
            $url = $checkout->start($tenant, $package, $validated['billing_cycle'], $validated['coupon_code'] ?? null, $request->user()->email);
        } catch (ApiErrorException|\RuntimeException $e) {
            report($e);

            return redirect()->route('billing.index')->withErrors(['billing' => 'Stripe checkout is unavailable. Please retry or contact your administrator.']);
        }

        return redirect()->away($url);
    }

    public function stripePortal(Request $request, TenantBillingAccess $access, StripeGateway $gateway): RedirectResponse
    {
        $tenant = $access->tenant($request);
        if (! $tenant->stripe_customer_id) {
            return redirect()->route('billing.index')->withErrors(['billing' => 'Start a Stripe subscription before opening the billing portal.']);
        }
        try {
            return redirect()->away($gateway->portal($tenant->stripe_customer_id, route('billing.index')));
        } catch (ApiErrorException|\RuntimeException $e) {
            report($e);

            return redirect()->route('billing.index')->withErrors(['billing' => 'Stripe billing portal is unavailable. Please contact your administrator.']);
        }
    }

    public function processPaypal(Request $request, TenantBillingAccess $access, BillingService $billing): RedirectResponse
    {
        $tenant = $access->tenant($request);
        if ($tenant->stripe_customer_id || $tenant->stripe_subscription_id) {
            return redirect()->route('billing.index')->withErrors(['billing' => 'This workspace uses Stripe. Choose a Stripe plan or manage your subscription.']);
        }

        $validated = $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'paypal_payer_email' => ['nullable', 'email'],
        ]);

        $package = Package::findOrFail($validated['package_id']);

        $payment = $billing->processPaypalPayment(
            $tenant,
            $package,
            $validated['billing_cycle'],
            $validated['coupon_code'] ?? null,
            [
                'payer_email' => $validated['paypal_payer_email'] ?? $request->user()?->email,
            ]
        );

        return redirect()->route('billing.index')->with('status', "Payment approved! Activated {$package->name} via PayPal. Order ID: {$payment->gateway_transaction_id}");
    }

    /**
     * Display printable/HTML official invoice.
     */
    public function viewInvoice(TenantPayment $payment, Request $request, TenantBillingAccess $access, InvoiceService $invoiceService): View
    {
        $access->payment($request, $payment);

        $payment->load(['tenant', 'package', 'coupon']);
        $invoiceData = $invoiceService->formatInvoiceData($payment);

        return view('pages.invoice', [
            'payment' => $payment,
            'tenant' => $payment->tenant,
            'invoice' => $invoiceData,
        ]);
    }

    /**
     * Resend / send invoice email to tenant admin.
     */
    public function sendInvoice(TenantPayment $payment, Request $request, TenantBillingAccess $access, InvoiceService $invoiceService): RedirectResponse
    {
        $access->payment($request, $payment);
        $validated = $request->validate(['recipient_email' => ['nullable', 'email', 'max:255']]);
        $recipient = $validated['recipient_email'] ?? null;
        $sent = $invoiceService->generateAndSendInvoice($payment, $recipient);

        if ($sent) {
            return redirect()->back()->with('status', "Invoice #{$payment->getInvoiceNumber()} emailed successfully!");
        }

        return redirect()->back()->withErrors(['invoice' => 'Could not send invoice email. Please verify admin email configuration.']);
    }
}
