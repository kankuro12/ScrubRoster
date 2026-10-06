<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'tenant_id', 'user_id', 'title', 'category',
    'priority', 'status', 'created_by_role',
    'last_responded_at', 'resolved_at',
])]
class TenantQuery extends Model
{
    use BelongsToTenant, HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_WAITING_TENANT = 'waiting_tenant_response';
    public const STATUS_TENANT_RESPONDED = 'tenant_responded';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const CATEGORY_GENERAL = 'general';
    public const CATEGORY_BILLING = 'billing';
    public const CATEGORY_QUOTA = 'quota_upgrade';
    public const CATEGORY_COMPLIANCE = 'compliance';
    public const CATEGORY_SUPPORT = 'technical_support';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    protected function casts(): array
    {
        return [
            'last_responded_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TenantQueryMessage::class)->oldest();
    }

    public function latestMessage(): BelongsTo
    {
        return $this->belongsTo(TenantQueryMessage::class, 'id', 'tenant_query_id')->latestOfMany();
    }

    public function isAwaitingTenant(): bool
    {
        return $this->status === self::STATUS_WAITING_TENANT;
    }

    public function isResolved(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED], true);
    }
}
