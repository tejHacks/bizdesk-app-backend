<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'number', 'payment_method', 'subtotal', 'discount', 'total', 'cost_total', 'note', 'sold_at'])]
class Sale extends Model
{
    public const PAYMENT_METHODS = ['cash', 'card', 'transfer'];

    protected function casts(): array
    {
        return [
            'sold_at' => 'datetime',
            'subtotal' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'cost_total' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getReceiptNoAttribute(): string
    {
        return 'INV-'.str_pad((string) $this->number, 5, '0', STR_PAD_LEFT);
    }

    public function getProfitAttribute(): int
    {
        return $this->total - $this->cost_total;
    }
}
