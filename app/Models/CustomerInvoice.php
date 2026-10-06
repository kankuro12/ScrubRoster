<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'tenant_id', 'client_id', 'invoice_number', 'invoice_date', 'due_date',
    'status', 'subtotal', 'tax_rate', 'tax_amount', 'discount_amount',
    'total_amount', 'currency', 'recipient_name', 'recipient_email',
    'billing_address', 'notes', 'terms', 'sent_at', 'paid_at',
    'created_by', 'updated_by',
])]
class CustomerInvoice extends Model
{
    use Auditable, BelongsToTenant, HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerInvoiceItem::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('total_price');
        $taxRate = (float) ($this->tax_rate ?? 0.0);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $discountAmount = (float) ($this->discount_amount ?? 0.0);
        $totalAmount = max(0.00, round($subtotal + $taxAmount - $discountAmount, 2));

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);
    }

    public static function generateNextInvoiceNumber(int $tenantId): string
    {
        $year = date('Y');
        $prefix = "INV-{$year}-";

        $lastInvoice = self::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        if ($lastInvoice && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $lastInvoice->invoice_number, $matches)) {
            $seq = intval($matches[1]) + 1;
        } else {
            $seq = 1001;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
