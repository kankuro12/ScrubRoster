<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id', 'tenant_id', 'package_id', 'coupon_id', 'billing_cycle', 'base_amount', 'discount_amount', 'total_amount', 'currency', 'status', 'stripe_session_id', 'expires_at', 'metadata'])]
class StripeCheckoutAttempt extends Model
{
    use BelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['base_amount' => 'decimal:2', 'discount_amount' => 'decimal:2', 'total_amount' => 'decimal:2', 'expires_at' => 'datetime', 'metadata' => 'array'];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
