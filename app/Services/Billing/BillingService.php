<?php

namespace App\Services\Billing;

use App\Models\Coupon;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\TenantPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingService
{
    public function __construct(
        protected ?InvoiceService $invoiceService = null
    ) {
        $this->invoiceService = $invoiceService ?? new InvoiceService;
    }

    /**
     * Validate coupon code for a given base amount.
     */
    public function validateCoupon(?string $code, float $amount): array
    {
        if (empty($code)) {
            return [
                'valid' => false,
                'coupon' => null,
                'discount' => 0.0,
                'final_amount' => $amount,
                'message' => 'No coupon provided',
            ];
        }

        $coupon = Coupon::where('code', strtoupper(trim($code)))->first();

        if (! $coupon) {
            return [
                'valid' => false,
                'coupon' => null,
                'discount' => 0.0,
                'final_amount' => $amount,
                'message' => 'Invalid coupon code.',
            ];
        }

        if (! $coupon->isValid()) {
            return [
                'valid' => false,
                'coupon' => null,
                'discount' => 0.0,
                'final_amount' => $amount,
                'message' => 'Coupon has expired or reached maximum redemption limit.',
            ];
        }

        $discount = $coupon->calculateDiscount($amount);
        $finalAmount = max(0.0, round($amount - $discount, 2));

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount' => $discount,
            'final_amount' => $finalAmount,
            'message' => "Coupon '{$coupon->code}' applied successfully! (".($coupon->discount_type === 'percentage' ? "{$coupon->discount_value}% off" : "\${$coupon->discount_value} off").')',
        ];
    }

    public function validatePackageCoupon(Package $package, string $cycle, ?string $code): array
    {
        return $this->validateCoupon($code, (float) ($cycle === 'yearly' ? $package->price_yearly : $package->price_monthly));
    }

    /**
     * Process PayPal checkout for a tenant subscription.
     */
    public function processPaypalPayment(Tenant $tenant, Package $package, string $billingCycle, ?string $couponCode, array $paypalDetails): TenantPayment
    {
        $basePrice = $billingCycle === 'yearly' ? (float) $package->price_yearly : (float) $package->price_monthly;
        $couponCheck = $this->validateCoupon($couponCode, $basePrice);
        $finalAmount = $couponCheck['final_amount'];
        $coupon = $couponCheck['coupon'];

        $orderId = ! empty($paypalDetails['order_id'])
            ? $paypalDetails['order_id']
            : 'PAYPAL-ORD-'.strtoupper(Str::random(16));

        $payment = DB::transaction(function () use ($tenant, $package, $billingCycle, $coupon, $finalAmount, $orderId, $paypalDetails) {
            $payment = TenantPayment::create([
                'tenant_id' => $tenant->id,
                'package_id' => $package->id,
                'coupon_id' => $coupon?->id,
                'payment_gateway' => 'paypal',
                'gateway_transaction_id' => $orderId,
                'amount' => $finalAmount,
                'currency' => 'USD',
                'billing_cycle' => $billingCycle,
                'status' => 'completed',
                'metadata' => [
                    'paypal_payer_id' => $paypalDetails['payer_id'] ?? 'PAYER-'.strtoupper(Str::random(10)),
                    'payer_email' => $paypalDetails['payer_email'] ?? null,
                    'paid_at' => now()->toIso8601String(),
                ],
            ]);

            if ($coupon) {
                $coupon->increment('redeemed_count');
            }

            $extension = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();

            $tenant->update([
                'package_id' => $package->id,
                'subscription_status' => 'active',
                'billing_cycle' => $billingCycle,
                'billing_provider' => 'paypal',
                'billing_subscription_id' => $orderId,
                'subscription_ends_at' => $extension,
            ]);

            return $payment;
        });

        // Auto-generate invoice and send email to tenant
        $this->invoiceService->generateAndSendInvoice($payment);

        return $payment;
    }
}
