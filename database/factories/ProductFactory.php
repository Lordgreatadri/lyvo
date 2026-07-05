<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\BusinessCategory;
use App\Models\OperatorProfile;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'operator_profile_id' => OperatorProfile::factory()->approved(),
            'business_category_id' => BusinessCategory::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 5, 5000),
            'currency' => 'GHS',
            'quantity' => fake()->numberBetween(1, 50),
            'status' => ProductStatus::Draft->value,
        ];
    }

    /** A live, publicly-visible item. */
    public function published(): static
    {
        return $this->state(fn () => [
            'status' => ProductStatus::Active->value,
            'published_at' => now()->subDay(),
        ]);
    }

    /** A currently-boosted (promoted) item. */
    public function boosted(int $weight = 100): static
    {
        return $this->state(fn () => [
            'status' => ProductStatus::Active->value,
            'published_at' => now()->subDay(),
            'is_featured' => true,
            'boost_weight' => $weight,
            'boosted_until' => now()->addDays(7),
        ]);
    }
}
