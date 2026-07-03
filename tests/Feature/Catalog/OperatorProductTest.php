<?php

namespace Tests\Feature\Catalog;

use App\Enums\AccountType;
use App\Enums\ProductStatus;
use App\Enums\UserStatus;
use App\Models\OperatorProfile;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers the operator catalogue workspace: creating, publishing and guarding
 * items. Ownership and role gates (ProductPolicy) are exercised here.
 */
class OperatorProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    /** Build an approved, fully-verified operator with the operator role. */
    private function operator(): User
    {
        $user = User::factory()->create([
            'account_type' => AccountType::Operator->value,
            'status' => UserStatus::Active->value,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        $user->assignRole(AccountType::Operator->defaultRole());

        OperatorProfile::factory()->approved()->create(['user_id' => $user->id]);

        return $user->refresh();
    }

    public function test_operator_can_create_an_item(): void
    {
        $user = $this->operator();

        $this->actingAs($user)
            ->post(route('operator.products.store'), [
                'name' => 'Handmade Sandals',
                'price' => 150,
                'quantity' => 10,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'operator_profile_id' => $user->operatorProfile->id,
            'name' => 'Handmade Sandals',
            'status' => ProductStatus::Draft->value,
        ]);
    }

    public function test_operator_can_upload_item_images(): void
    {
        Storage::fake('public');
        $user = $this->operator();

        $this->actingAs($user)
            ->post(route('operator.products.store'), [
                'name' => 'Beaded Necklace',
                'price' => 80,
                'images' => [UploadedFile::fake()->image('necklace.jpg')],
            ])
            ->assertRedirect();

        $product = Product::firstWhere('name', 'Beaded Necklace');
        $this->assertCount(1, $product->getMedia('images'));
    }

    public function test_operator_can_publish_and_unpublish(): void
    {
        $user = $this->operator();
        $product = Product::factory()->for($user->operatorProfile, 'operator')->create();

        $this->actingAs($user)
            ->patch(route('operator.products.publish', $product))
            ->assertRedirect();
        $this->assertTrue($product->fresh()->isPublished());

        $this->actingAs($user)
            ->patch(route('operator.products.unpublish', $product))
            ->assertRedirect();
        $this->assertSame(ProductStatus::Draft, $product->fresh()->status);
    }

    public function test_operator_cannot_touch_another_operators_item(): void
    {
        $owner = $this->operator();
        $intruder = $this->operator();

        $product = Product::factory()->for($owner->operatorProfile, 'operator')->create();

        $this->actingAs($intruder)
            ->patch(route('operator.products.update', $product), ['name' => 'Hijacked'])
            ->assertForbidden();

        $this->actingAs($intruder)
            ->delete(route('operator.products.destroy', $product))
            ->assertForbidden();
    }

    public function test_customer_cannot_access_the_catalogue_workspace(): void
    {
        $customer = User::factory()->create([
            'account_type' => AccountType::Customer->value,
            'status' => UserStatus::Active->value,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        $customer->assignRole(AccountType::Customer->defaultRole());

        $this->actingAs($customer)
            ->get(route('operator.products.index'))
            ->assertRedirect();
    }
}
