<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id', 'scope', 'key', 'value', 'description',
])]
class Setting extends Model
{
    use Auditable, BelongsToTenant;

    public const SCOPE_SYSTEM = 'system';

    public const SCOPE_ORGANIZATION = 'organization';

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
