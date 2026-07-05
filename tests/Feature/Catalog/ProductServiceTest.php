<?php

namespace Tests\Feature\Catalog;

use App\Enums\ProductStatus;
use App\Models\OperatorProfile;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domain\Catalog\ProductService;
use Tests\TestCase;

/**
 * Exercises the catalogue write service directly: slug uniqueness, stock-derived
 * status and the publish lifecycle.
 */
class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): ProductService
    {
        return app(ProductService::class);
    }

    public function test_create_generates_a_unique_slug_per_operator(): void
    {
        $operator = OperatorProfile::factory()->approved()->create();

        $first = $this->service()->create($operator, ['name' => 'Ankara Dress', 'price' => 120]);
        $second = $this->service()->create($operator, ['name' => 'Ankara Dress', 'price' => 130]);

        $this->assertSame('ankara-dress', $first->slug);
        $this->assertSame('ankara-dress-2', $second->slug);
        $this->assertSame(ProductStatus::Draft, $first->status);
    }

    public function test_publish_makes_the_item_active_and_timestamped(): void
    {
        $operator = OperatorProfile::factory()->approved()->create();
        $product = $this->service()->create($operator, ['name' => 'Sneakers', 'price' => 400, 'quantity' => 5]);

        $this->service()->publish($product);

        $this->assertSame(ProductStatus::Active, $product->fresh()->status);
        $this->assertNotNull($product->fresh()->published_at);
        $this->assertTrue($product->fresh()->isPublished());
    }

    public function test_zero_stock_flips_a_published_item_to_sold_out(): void
    {
        $operator = OperatorProfile::factory()->approved()->create();
        $product = $this->service()->create($operator, ['name' => 'Watch', 'price' => 900, 'quantity' => 1]);
        $this->service()->publish($product);

        $this->service()->update($product, ['quantity' => 0]);

        $this->assertSame(ProductStatus::SoldOut, $product->fresh()->status);
        $this->assertFalse($product->fresh()->isInStock());
    }

    public function test_restocking_flips_sold_out_back_to_active(): void
    {
        $operator = OperatorProfile::factory()->approved()->create();
        $product = Product::factory()->for($operator, 'operator')->create([
            'status' => ProductStatus::SoldOut->value,
            'quantity' => 0,
            'published_at' => now()->subDay(),
        ]);

        $this->service()->update($product, ['quantity' => 10]);

        $this->assertSame(ProductStatus::Active, $product->fresh()->status);
    }
}
