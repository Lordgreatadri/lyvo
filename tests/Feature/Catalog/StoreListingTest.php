<?php

namespace Tests\Feature\Catalog;

use App\Models\BusinessCategory;
use App\Models\OperatorProfile;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the public marketplace: only published items are shown, paid boosts
 * float to the top, category filtering works and unpublished items 404.
 */
class StoreListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_lists_only_published_items(): void
    {
        $operator = OperatorProfile::factory()->approved()->create();

        $live = Product::factory()->for($operator, 'operator')->published()->create(['name' => 'Live Item']);
        $draft = Product::factory()->for($operator, 'operator')->create(['name' => 'Draft Item']);

        $this->get(route('store.index'))
            ->assertOk()
            ->assertSee('Live Item')
            ->assertDontSee('Draft Item');
    }

    public function test_boosted_items_are_ordered_first(): void
    {
        $operator = OperatorProfile::factory()->approved()->create();

        Product::factory()->for($operator, 'operator')->published()->create(['name' => 'Plain Item']);
        Product::factory()->for($operator, 'operator')->boosted(500)->create(['name' => 'Boosted Item']);

        $response = $this->get(route('store.index'))->assertOk();

        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, 'Plain Item'),
            strpos($content, 'Boosted Item'),
            'Boosted item should render before the plain item.',
        );
    }

    public function test_category_filter_scopes_the_listing(): void
    {
        $operator = OperatorProfile::factory()->approved()->create();
        $fashion = BusinessCategory::factory()->create(['name' => 'Fashion', 'slug' => 'fashion']);
        $electronics = BusinessCategory::factory()->create(['name' => 'Electronics', 'slug' => 'electronics']);

        Product::factory()->for($operator, 'operator')->published()->create(['name' => 'Kente Cloth', 'business_category_id' => $fashion->id]);
        Product::factory()->for($operator, 'operator')->published()->create(['name' => 'Bluetooth Speaker', 'business_category_id' => $electronics->id]);

        $this->get(route('store.index', ['category' => 'fashion']))
            ->assertOk()
            ->assertSee('Kente Cloth')
            ->assertDontSee('Bluetooth Speaker');
    }

    public function test_product_page_shows_published_item_and_counts_a_view(): void
    {
        $operator = OperatorProfile::factory()->approved()->create();
        $product = Product::factory()->for($operator, 'operator')->published()->create(['name' => 'Leather Bag']);

        $this->get(route('store.show', $product))
            ->assertOk()
            ->assertSee('Leather Bag')
            ->assertSee('Sign in to pay with escrow');

        $this->assertSame(1, $product->fresh()->views);
    }

    public function test_unpublished_product_page_returns_404(): void
    {
        $operator = OperatorProfile::factory()->approved()->create();
        $draft = Product::factory()->for($operator, 'operator')->create();

        $this->get(route('store.show', $draft))->assertNotFound();
    }
}
