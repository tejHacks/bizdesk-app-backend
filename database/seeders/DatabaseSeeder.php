<?php

namespace Database\Seeders;

use App\Actions\RecordSale;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::factory()->create([
            'name' => 'Ada Okafor',
            'business_name' => "Ada's Store",
            'currency' => '₦',
            'email' => 'owner@bizdesk.test',
            'password' => 'password',
        ]);

        $products = collect([
            ['Bottled Water 75cl', 'BW-75', 'Drinks', 30000, 18000, 120],
            ['Coca-Cola 50cl', 'CC-50', 'Drinks', 50000, 35000, 60],
            ['Bread (Large)', 'BR-L', 'Bakery', 150000, 110000, 8],
            ['Meat Pie', 'MP-01', 'Bakery', 80000, 50000, 25],
            ['Indomie (Pack)', 'IN-40', 'Groceries', 1200000, 1000000, 4],
            ['Rice 5kg', 'RC-5', 'Groceries', 950000, 820000, 15],
            ['Phone Charger', 'EL-CH', 'Electronics', 450000, 280000, 10],
            ['Earphones', 'EL-EP', 'Electronics', 350000, 200000, 2],
        ])->map(fn ($p) => $owner->products()->create([
            'name' => $p[0], 'sku' => $p[1], 'category' => $p[2],
            'price' => $p[3], 'cost' => $p[4], 'stock' => $p[5], 'low_stock_at' => $p[2] === 'Electronics' ? 3 : 10,
        ]));

        $customers = collect(['Tunde Bakare', 'Grace Hopper', 'Chidi Eze'])
            ->map(fn ($name) => $owner->customers()->create(['name' => $name, 'phone' => '0803'.random_int(1000000, 9999999)]));

        $record = app(RecordSale::class);
        $plan = [
            [6, [[0, 6], [3, 2]], 0, 'cash'],
            [5, [[1, 4], [2, 1]], null, 'card'],
            [4, [[5, 1], [0, 10]], 1, 'transfer'],
            [3, [[6, 1]], 2, 'transfer'],
            [2, [[3, 5], [1, 3]], null, 'cash'],
            [1, [[4, 1], [0, 4]], 0, 'cash'],
            [0, [[2, 2], [1, 2]], null, 'card'],
        ];

        foreach ($plan as [$daysAgo, $lines, $customer, $method]) {
            $sale = $record->handle(
                user: $owner,
                lines: array_map(fn ($l) => ['product_id' => $products[$l[0]]->id, 'qty' => $l[1]], $lines),
                customerId: $customer === null ? null : $customers[$customer]->id,
                paymentMethod: $method,
            );
            $sale->update(['sold_at' => now()->subDays($daysAgo)->setTime(11, 30)]);
        }

        $owner->expenses()->createMany([
            ['category' => 'Transport', 'amount' => 250000, 'note' => 'Restock trip', 'spent_on' => today()->subDays(3)],
            ['category' => 'Utilities', 'amount' => 800000, 'note' => 'Electricity', 'spent_on' => today()->subDay()],
        ]);
    }
}
