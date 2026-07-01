<?php

namespace Database\Seeders;

use App\Models\BusinessCategory;
use Illuminate\Database\Seeder;

/**
 * BusinessCategorySeeder
 * ----------------------
 * Seeds the operator business categories used across the directory and the
 * operator onboarding form (previously hard-coded in App\Support\DemoData).
 */
class BusinessCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Fashion', 'slug' => 'fashion', 'icon' => 'shirt'],
            ['name' => 'Electronics', 'slug' => 'electronics', 'icon' => 'cpu'],
            ['name' => 'Beauty', 'slug' => 'beauty', 'icon' => 'sparkles'],
            ['name' => 'Food', 'slug' => 'food', 'icon' => 'utensils'],
            ['name' => 'Services', 'slug' => 'services', 'icon' => 'briefcase'],
            ['name' => 'Automotive', 'slug' => 'automotive', 'icon' => 'car'],
            ['name' => 'Home & Living', 'slug' => 'home-living', 'icon' => 'home'],
            ['name' => 'Health', 'slug' => 'health', 'icon' => 'heart'],
        ];

        foreach ($categories as $index => $category) {
            BusinessCategory::updateOrCreate(
                ['slug' => $category['slug']],
                array_merge($category, ['sort_order' => $index, 'is_active' => true]),
            );
        }
    }
}
