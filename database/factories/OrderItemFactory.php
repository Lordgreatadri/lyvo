<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 20, 3000);
        $quantity = fake()->numberBetween(1, 3);

        return [
            'product_id' => Product::factory(),
            'name' => fake()->words(3, true),
            'unit_price' => $price,
            'quantity' => $quantity,
            'line_total' => round($price * $quantity, 2),
        ];
    }
}
