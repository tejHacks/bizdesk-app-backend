<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;

#[WithoutTimestamps]
#[Fillable(['product_id', 'name', 'price', 'cost', 'qty', 'total'])]
class SaleItem extends Model
{
    protected function casts(): array
    {
        return ['price' => 'integer', 'cost' => 'integer', 'qty' => 'integer', 'total' => 'integer'];
    }
}
