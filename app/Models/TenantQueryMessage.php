<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_query_id', 'user_id', 'sender_type',
    'message', 'attachments',
])]
class TenantQueryMessage extends Model
{
    use HasFactory;

    public const SENDER_TENANT_ADMIN = 'tenant_admin';
    public const SENDER_PLATFORM_ADMIN = 'platform_admin';

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
        ];
    }

    public function tenantQuery(): BelongsTo
    {
        return $this->belongsTo(TenantQuery::class, 'tenant_query_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFromPlatform(): bool
    {
        return $this->sender_type === self::SENDER_PLATFORM_ADMIN;
    }

    public function isFromTenant(): bool
    {
        return $this->sender_type === self::SENDER_TENANT_ADMIN;
    }
}
