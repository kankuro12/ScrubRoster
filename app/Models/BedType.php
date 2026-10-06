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
    'tenant_id', 'name', 'description', 'active', 'sort_order',
])]
class BedType extends Model
{
    use Auditable, BelongsToTenant, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function propertyBeds(): HasMany
    {
        return $this->hasMany(PropertyBed::class);
    }
}
