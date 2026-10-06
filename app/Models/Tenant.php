<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'slug', 'domain', 'status', 'settings',
    'package_id', 'subscription_status', 'trial_ends_at', 'subscription_ends_at',
    'billing_cycle', 'billing_provider', 'billing_customer_id', 'billing_subscription_id',
    'max_employees_override', 'google_calendar_sync_enabled', 'google_calendar_id',
    'google_calendar_auto_sync', 'google_calendar_last_synced_at',
    'google_sheet_sync_enabled', 'google_sheet_url_or_id', 'google_sheet_auto_sync',
    'google_sheet_last_synced_at', 'google_sync_token',
])]
class Tenant extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'google_calendar_last_synced_at' => 'datetime',
            'google_sheet_last_synced_at' => 'datetime',
            'google_calendar_sync_enabled' => 'boolean',
            'google_calendar_auto_sync' => 'boolean',
            'google_sheet_sync_enabled' => 'boolean',
            'google_sheet_auto_sync' => 'boolean',
            'max_employees_override' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function createWithSlug(string $name, ?string $slug = null): self
    {
        $baseSlug = Str::slug($slug ?: $name);
        $candidateSlug = $baseSlug;
        $counter = 1;

        while (static::where('slug', $candidateSlug)->exists()) {
            $candidateSlug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return static::create([
            'name' => $name,
            'slug' => $candidateSlug,
            'status' => 'active',
        ]);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function admins(): HasMany
    {
        return $this->hasMany(User::class)->where('role', User::ROLE_ADMIN);
    }

    public function managers(): HasMany
    {
        return $this->hasMany(User::class)->where('role', User::ROLE_SUPERVISOR);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(User::class)->where('role', User::ROLE_CLEANER);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function qrLoginTokens(): HasMany
    {
        return $this->hasMany(QrLoginToken::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TenantPayment::class);
    }

    public function queries(): HasMany
    {
        return $this->hasMany(TenantQuery::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(TenantAlert::class);
    }

    public function unreadAlerts(): HasMany
    {
        return $this->hasMany(TenantAlert::class)->where('is_read', false);
    }

    public function getMaxEmployees(): int
    {
        if ($this->max_employees_override !== null) {
            return (int) $this->max_employees_override;
        }

        return $this->package ? (int) $this->package->max_employees : 5;
    }

    public function getActiveEmployeesCount(): int
    {
        return $this->employees()->where('status', 'active')->count();
    }

    public function canAddEmployee(): bool
    {
        $max = $this->getMaxEmployees();
        if ($max <= 0) { // 0 represents unlimited
            return true;
        }

        return $this->getActiveEmployeesCount() < $max;
    }

    public function isOnTrial(): bool
    {
        if ($this->subscription_status !== 'trial') {
            return false;
        }

        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function getRemainingTrialDays(): int
    {
        if (! $this->isOnTrial()) {
            return 0;
        }

        return max(0, (int) ceil(now()->diffInDays($this->trial_ends_at, false)));
    }

    public function isSubscriptionActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->isOnTrial()) {
            return true;
        }

        if ($this->subscription_status === 'active') {
            return ! $this->subscription_ends_at || $this->subscription_ends_at->isFuture();
        }

        return false;
    }

    public function ensureGoogleSyncToken(): string
    {
        if (! $this->google_sync_token) {
            $this->google_sync_token = \Illuminate\Support\Str::random(40);
            $this->saveQuietly();
        }

        return $this->google_sync_token;
    }
}

