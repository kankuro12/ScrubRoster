<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'name', 'discount_type', 'discount_value',
    'max_redemptions', 'redeemed_count', 'expires_at', 'is_active',
])]
class Coupon extends Model
{
    use HasFactory;

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'max_redemptions' => 'integer',
            'redeemed_count' => 'integer',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TenantPayment::class);
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_redemptions !== null && $this->redeemed_count >= $this->max_redemptions) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if (! $this->isValid()) {
            return 0.0;
        }

        if ($this->discount_type === self::TYPE_PERCENTAGE) {
            $discount = ($amount * (float) $this->discount_value) / 100;
        } else {
            $discount = (float) $this->discount_value;
        }

        return round(min($discount, $amount), 2);
    }
}
