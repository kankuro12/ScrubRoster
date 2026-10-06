<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id', 'type', 'severity', 'title', 'message',
    'action_url', 'action_label', 'is_read', 'read_at',
    'email_sent_at', 'email_recipient',
])]
class TenantAlert extends Model
{
    use BelongsToTenant, HasFactory;

    public const TYPE_TRIAL_EXPIRING = 'trial_expiring';
    public const TYPE_TRIAL_EXPIRED = 'trial_expired';
    public const TYPE_QUOTA_WARNING = 'quota_warning';
    public const TYPE_QUOTA_EXCEEDED = 'quota_exceeded';
    public const TYPE_SUBSCRIPTION_RENEWAL = 'subscription_renewal';
    public const TYPE_PAYMENT_SUCCEEDED = 'payment_succeeded';
    public const TYPE_QUERY_RESPONSE = 'query_response';
    public const TYPE_SYSTEM_NOTICE = 'system_notice';

    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_DANGER = 'danger';
    public const SEVERITY_SUCCESS = 'success';

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'email_sent_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }
}
