<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Services\Billing\BillingService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantBillingController extends Controller
{
    public function index(Request $request, TenantContext $context): View
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;

        if (! $tenant) {
            abort(404, 'No active tenant workspace found.');
        }

        $packages = Package::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price_monthly')
            ->get();

        $payments = TenantPayment::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with(['package', 'coupon'])
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
        ]);
    }

    public function validateCoupon(Request $request, BillingService $billing): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $result = $billing->validateCoupon($request->input('code'), (float) $request->input('amount'));

        return response()->json($result);
    }

    public function processStripe(Request $request, TenantContext $context, BillingService $billing): RedirectResponse
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;
        if (! $tenant) {
            return redirect()->back()->withErrors(['billing' => 'Tenant context missing.']);
        }

        $validated = $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'card_name' => ['required', 'string', 'max:100'],
            'card_number' => ['required', 'string', 'min:12'],
            'card_expiry' => ['required', 'string'],
            'card_cvc' => ['required', 'string', 'min:3', 'max:4'],
        ]);

        $package = Package::findOrFail($validated['package_id']);

        $cleanCard = preg_replace('/\s+/', '', $validated['card_number']);
        $last4 = substr($cleanCard, -4) ?: '4242';

        $payment = $billing->processStripePayment(
            $tenant,
            $package,
            $validated['billing_cycle'],
            $validated['coupon_code'] ?? null,
            [
                'card_name' => $validated['card_name'],
                'last4' => $last4,
                'brand' => 'Visa/Mastercard',
            ]
        );

        return redirect()->route('billing.index')->with('status', "Payment successful! Upgraded to {$package->name} via Stripe. Transaction ID: {$payment->gateway_transaction_id}");
    }

    public function processPaypal(Request $request, TenantContext $context, BillingService $billing): RedirectResponse
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;
        if (! $tenant) {
            return redirect()->back()->withErrors(['billing' => 'Tenant context missing.']);
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
    public function viewInvoice(TenantPayment $payment, Request $request, TenantContext $context, \App\Services\Billing\InvoiceService $invoiceService): View
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;

        if ($payment->tenant_id !== $tenant?->id && $request->user()?->role !== \App\Models\User::ROLE_ADMIN && $request->user()?->tenant_id !== null) {
            abort(403, 'Unauthorized invoice access.');
        }

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
    public function sendInvoice(TenantPayment $payment, Request $request, \App\Services\Billing\InvoiceService $invoiceService): RedirectResponse
    {
        $recipient = $request->input('recipient_email') ?: null;
        $sent = $invoiceService->generateAndSendInvoice($payment, $recipient);

        if ($sent) {
            return redirect()->back()->with('status', "Invoice #{$payment->getInvoiceNumber()} emailed successfully!");
        }

        return redirect()->back()->withErrors(['invoice' => 'Could not send invoice email. Please verify admin email configuration.']);
    }
}
