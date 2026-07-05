<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\BusinessCategorySeeder;
use Database\Seeders\DemoOrderSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            BusinessCategorySeeder::class,
            DemoUserSeeder::class,
            DemoProductSeeder::class,
            DemoOrderSeeder::class,
        ]);
    }
}
