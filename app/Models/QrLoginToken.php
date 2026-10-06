<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['tenant_id', 'user_id', 'token', 'expires_at', 'used_at'])]
class QrLoginToken extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public static function generateForUser(User $user, int $lifetimeMinutes = 30): self
    {
        // Expire older unused tokens for this user
        static::where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->delete();

        return static::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'token' => Str::random(48),
            'expires_at' => now()->addMinutes($lifetimeMinutes),
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return $this->expires_at->isFuture() && $this->used_at === null;
    }

    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())->whereNull('used_at');
    }

    public function markUsed(): void
    {
        $this->update(['used_at' => now()]);
    }
}
