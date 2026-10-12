<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id', 'package_id', 'coupon_id', 'payment_gateway',
    'gateway_transaction_id', 'invoice_number', 'amount', 'currency', 'billing_cycle',
    'status', 'metadata', 'invoice_sent_at', 'invoice_recipient_email',
    'stripe_invoice_id', 'stripe_subscription_id', 'stripe_checkout_attempt_id',
])]
class TenantPayment extends Model
{
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
            'invoice_sent_at' => 'datetime',
        ];
    }

    public function getInvoiceNumber(): string
    {
        if ($this->invoice_number) {
            return $this->invoice_number;
        }

        return 'INV-' . date('Y') . '-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function stripeCheckoutAttempt(): BelongsTo
    {
        return $this->belongsTo(StripeCheckoutAttempt::class);
    }
}
