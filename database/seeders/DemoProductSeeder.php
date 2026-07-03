<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\OperatorProfile;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * DemoProductSeeder
 * -----------------
 * Publishes a small catalogue for the approved demo operator so the public
 * marketplace has content to review. Idempotent: keyed on operator + slug.
 */
class DemoProductSeeder extends Seeder
{
    public function run(): void
    {
        $operator = User::where('email', 'operator@lyvo.test')->first()?->operatorProfile;

        if (! $operator instanceof OperatorProfile) {
            return;
        }

        $items = [
            ['name' => 'Ankara Wrap Dress', 'price' => 320.00, 'quantity' => 12, 'boost' => 300],
            ['name' => 'Kente Blazer', 'price' => 540.00, 'quantity' => 6, 'boost' => 150],
            ['name' => 'Beaded Statement Necklace', 'price' => 95.00, 'quantity' => 20, 'boost' => 0],
            ['name' => 'Handwoven Leather Sandals', 'price' => 180.00, 'quantity' => 15, 'boost' => 0],
            ['name' => 'Adinkra Print Tote Bag', 'price' => 130.00, 'quantity' => 25, 'boost' => 0],
            ['name' => 'Custom Tailored Agbada', 'price' => 890.00, 'quantity' => 4, 'boost' => 0],
        ];

        foreach ($items as $item) {
            Product::updateOrCreate(
                ['operator_profile_id' => $operator->id, 'slug' => Str::slug($item['name'])],
                [
                    'business_category_id' => $operator->business_category_id,
                    'name' => $item['name'],
                    'description' => 'A signature piece from Adwoa Couture — crafted with premium materials and finished by hand.',
                    'price' => $item['price'],
                    'currency' => 'GHS',
                    'quantity' => $item['quantity'],
                    'status' => ProductStatus::Active,
                    'is_featured' => $item['boost'] > 0,
                    'boost_weight' => $item['boost'],
                    'boosted_until' => $item['boost'] > 0 ? now()->addDays(14) : null,
                    'published_at' => now()->subDays(2),
                ],
            );
        }
    }
}
