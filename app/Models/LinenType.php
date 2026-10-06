<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'tenant_id', 'name', 'rate', 'description', 'active', 'sort_order',
])]
class LinenType extends Model
{
    use Auditable, BelongsToTenant, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function propertyLinens(): HasMany
    {
        return $this->hasMany(PropertyLinen::class);
    }
}
