<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'slug', 'description', 'price_monthly', 'price_yearly',
    'max_employees', 'trial_days', 'features', 'has_google_calendar',
    'has_google_sheets', 'has_qr_login', 'is_active', 'is_popular', 'sort_order',
])]
class Package extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'max_employees' => 'integer',
            'trial_days' => 'integer',
            'features' => 'array',
            'has_google_calendar' => 'boolean',
            'has_google_sheets' => 'boolean',
            'has_qr_login' => 'boolean',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public static function createWithSlug(array $attributes): self
    {
        if (empty($attributes['slug']) && ! empty($attributes['name'])) {
            $baseSlug = Str::slug($attributes['name']);
            $candidate = $baseSlug;
            $i = 1;
            while (static::where('slug', $candidate)->exists()) {
                $candidate = "{$baseSlug}-{$i}";
                $i++;
            }
            $attributes['slug'] = $candidate;
        }

        return static::create($attributes);
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TenantPayment::class);
    }

    public function formattedMonthlyPrice(): string
    {
        return '$' . number_format($this->price_monthly, 2);
    }

    public function formattedYearlyPrice(): string
    {
        return '$' . number_format($this->price_yearly, 2);
    }
}
