<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $cost = fake()->numberBetween(100, 5000) * 100;

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Bottled Water', 'Bread', 'Rice 5kg', 'Phone Charger', 'Notebook', 'Soap', 'Tea']).' '.fake()->unique()->numberBetween(1, 99999),
            'sku' => strtoupper(fake()->unique()->bothify('??-####')),
            'category' => fake()->randomElement(['Drinks', 'Bakery', 'Groceries', 'Electronics']),
            'cost' => $cost,
            'price' => (int) round($cost * 1.3),
            'stock' => fake()->numberBetween(5, 100),
            'low_stock_at' => 5,
        ];
    }
}
