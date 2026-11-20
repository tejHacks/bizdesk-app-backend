<?php

namespace App\Exceptions;

use App\Models\Product;
use Exception;
use Illuminate\Http\JsonResponse;

class InsufficientStockException extends Exception
{
    public function __construct(public Product $product, public int $requested)
    {
        parent::__construct("Only {$product->stock} × {$product->name} left in stock.");
    }

    /** Laravel calls render() automatically when this exception is thrown. */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => 'insufficient_stock',
            'product_id' => $this->product->id,
            'available' => $this->product->stock,
            'requested' => $this->requested,
        ], 409);
    }
}
