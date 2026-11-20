<?php

namespace App\Actions;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Records a sale and deducts stock, all-or-nothing.
 *
 * - Runs in a database transaction: either every line and stock change is saved, or none.
 * - Locks the product rows (lockForUpdate) so two tills can't oversell the same stock.
 * - Copies name/price/cost onto each line so receipts never change later.
 */
class RecordSale
{
    /**
     * @param  array<int, array{product_id: int, qty: int}>  $lines
     */
    public function handle(
        User $user,
        array $lines,
        ?int $customerId = null,
        int $discount = 0,
        string $paymentMethod = 'cash',
        ?string $note = null,
    ): Sale {
        return DB::transaction(function () use ($user, $lines, $customerId, $discount, $paymentMethod, $note) {
            // Merge duplicate product lines: [{id:1, qty:2}, {id:1, qty:1}] -> {1: 3}
            $quantities = collect($lines)
                ->groupBy('product_id')
                ->map(fn ($group) => $group->sum('qty'));

            $products = $user->products()
                ->whereKey($quantities->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $items = [];
            foreach ($quantities as $productId => $qty) {
                /** @var Product $product */
                $product = $products[$productId];   // validated to exist & belong to the user

                if ($qty > $product->stock) {
                    throw new InsufficientStockException($product, $qty);
                }

                $product->decrement('stock', $qty);
                $items[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'cost' => $product->cost,
                    'qty' => $qty,
                    'total' => $product->price * $qty,
                ];
            }

            $subtotal = array_sum(array_column($items, 'total'));
            $discount = min($discount, $subtotal);

            $sale = $user->sales()->create([
                'number' => ($user->sales()->lockForUpdate()->max('number') ?? 0) + 1,
                'customer_id' => $customerId,
                'payment_method' => $paymentMethod,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $subtotal - $discount,
                'cost_total' => array_sum(array_map(fn ($i) => $i['cost'] * $i['qty'], $items)),
                'note' => $note,
                'sold_at' => now(),
            ]);
            $sale->items()->createMany($items);

            return $sale->load('items', 'customer');
        });
    }
}
