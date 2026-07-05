<?php

namespace Tests\Feature\Orders;

use App\Enums\AccountType;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\OperatorProfile;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\BusinessCategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke tests that every rewritten dashboard, orders and escrow view renders
 * without error against real database records for each role.
 */
class DashboardRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(BusinessCategorySeeder::class);
    }

    private function verifiedUser(AccountType $type): User
    {
        $user = User::factory()->create([
            'account_type' => $type->value,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'phone' => '020'.fake()->unique()->numerify('#######'),
        ]);
        $user->assignRole($type->defaultRole());

        return $user;
    }

    private function approvedOperator(): OperatorProfile
    {
        $user = $this->verifiedUser(AccountType::Operator);

        return OperatorProfile::factory()->approved()->create(['user_id' => $user->id]);
    }

    private function orderFor(OperatorProfile $operator, User $customer, OrderStatus $status): Order
    {
        $product = Product::factory()->for($operator, 'operator')->create([
            'status' => ProductStatus::Active->value,
            'published_at' => now()->subDay(),
            'price' => 150,
        ]);

        $order = Order::factory()->create([
            'operator_profile_id' => $operator->id,
            'customer_id' => $customer->id,
            'status' => $status->value,
            'subtotal' => 150,
            'total' => 150,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'unit_price' => 150,
            'quantity' => 1,
            'line_total' => 150,
        ]);

        return $order;
    }

    public function test_operator_dashboard_orders_and_customers_render(): void
    {
        $operator = $this->approvedOperator();
        $customer = $this->verifiedUser(AccountType::Customer);
        $order = $this->orderFor($operator, $customer, OrderStatus::Processing);

        $this->actingAs($operator->user);

        $this->get(route('operator.dashboard'))->assertOk();
        $this->get(route('operator.orders.index'))->assertOk()->assertSee($order->order_number);
        $this->get(route('operator.orders.show', $order))->assertOk();
        $this->get(route('operator.customers.index'))->assertOk();
        $this->get(route('operator.branding.edit'))->assertOk();
        $this->get(route('escrow.index'))->assertOk();
    }

    public function test_customer_dashboard_orders_and_escrow_render(): void
    {
        $operator = $this->approvedOperator();
        $customer = $this->verifiedUser(AccountType::Customer);
        $order = $this->orderFor($operator, $customer, OrderStatus::FundsHeld);

        $this->actingAs($customer);

        $this->get(route('customer.dashboard'))->assertOk();
        $this->get(route('customer.orders.index'))->assertOk()->assertSee($order->order_number);
        $this->get(route('customer.orders.show', $order))->assertOk();
        $this->get(route('escrow.index'))->assertOk();
    }

    public function test_admin_dashboard_and_orders_render(): void
    {
        $operator = $this->approvedOperator();
        $customer = $this->verifiedUser(AccountType::Customer);
        $order = $this->orderFor($operator, $customer, OrderStatus::Disputed);

        $admin = $this->verifiedUser(AccountType::Admin);
        $this->actingAs($admin);

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.orders.index'))->assertOk()->assertSee($order->order_number);
        $this->get(route('admin.orders.show', $order))->assertOk();
    }

    public function test_admin_verification_page_renders_and_approve_works(): void
    {
        $pending = OperatorProfile::factory()->create();
        $approved = OperatorProfile::factory()->approved()->create();
        $admin = $this->verifiedUser(AccountType::Admin);
        $this->actingAs($admin);

        // The operators index lists every operator, not just pending ones.
        $this->get(route('admin.operators.index'))
            ->assertOk()
            ->assertSee($pending->business_name)
            ->assertSee($approved->business_name);

        $this->get(route('admin.verification'))
            ->assertOk()
            ->assertSee($pending->business_name);

        $this->patch(route('admin.operators.approve', $pending))
            ->assertRedirect();

        $this->assertTrue($pending->fresh()->isApproved());
    }

    public function test_public_directory_and_home_render_with_real_operators(): void
    {
        $operator = $this->approvedOperator();
        Product::factory()->for($operator, 'operator')->create([
            'status' => ProductStatus::Active->value,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('home'))->assertOk()->assertSee($operator->business_name);
        $this->get(route('directory.index'))->assertOk()->assertSee($operator->business_name);
        $this->get(route('directory.show', $operator))->assertOk()->assertSee($operator->business_name);
    }
}
