<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'sku', 'category', 'price', 'cost', 'stock', 'low_stock_at'])]
class Product extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['price' => 'integer', 'cost' => 'integer', 'stock' => 'integer', 'low_stock_at' => 'integer'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    #[Scope]
    protected function lowStock(Builder $query): void
    {
        $query->whereColumn('stock', '<=', 'low_stock_at');
    }
}
