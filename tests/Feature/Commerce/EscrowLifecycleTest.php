<?php

namespace Tests\Feature\Commerce;

use App\Enums\OrderStatus;
use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Models\OperatorProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Src\Domain\Commerce\EscrowService;
use Src\Domain\Commerce\OrderService;
use Tests\TestCase;

/**
 * Walks the escrow lifecycle end-to-end: placing an order settles the (log)
 * payment which holds funds, the operator fulfils, and the buyer confirms to
 * release — plus the dispute/refund off-ramp and stock movement.
 */
class EscrowLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function product(int $stock = 5): Product
    {
        $operator = OperatorProfile::factory()->approved()->create();

        return Product::factory()->for($operator, 'operator')->create([
            'status' => ProductStatus::Active->value,
            'quantity' => $stock,
            'price' => 200,
            'published_at' => now()->subDay(),
        ]);
    }

    private function customer(): User
    {
        return User::factory()->create(['phone' => '+233201112233']);
    }

    public function test_placing_an_order_holds_funds_in_escrow_and_reserves_stock(): void
    {
        $product = $this->product(5);
        $customer = $this->customer();

        $order = app(OrderService::class)->place($customer, $product, 2, PaymentChannel::Mtn);

        $this->assertSame(OrderStatus::FundsHeld, $order->status);
        $this->assertSame(PaymentStatus::Successful, $order->payment->status);
        $this->assertSame(400.0, (float) $order->total);
        $this->assertSame(3, $product->fresh()->quantity, 'Stock should be reduced by the ordered quantity.');
        $this->assertSame(2, $product->fresh()->sold_count);
    }

    public function test_full_happy_path_releases_funds(): void
    {
        $product = $this->product();
        $customer = $this->customer();
        $seller = $product->operator->user;

        $order = app(OrderService::class)->place($customer, $product, 1, PaymentChannel::Mtn);
        $escrow = app(EscrowService::class);

        $escrow->markProcessing($order->fresh(), $seller);
        $this->assertSame(OrderStatus::Processing, $order->fresh()->status);

        $escrow->markDelivered($order->fresh(), $seller);
        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);

        $escrow->confirmDelivery($order->fresh(), $customer);
        $this->assertSame(OrderStatus::Released, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->released_at);

        // Every transition is audited.
        $this->assertGreaterThanOrEqual(4, $order->fresh()->events()->count());
    }

    public function test_dispute_can_be_refunded_and_restocks(): void
    {
        $product = $this->product(5);
        $customer = $this->customer();
        $admin = User::factory()->create();

        $order = app(OrderService::class)->place($customer, $product, 2, PaymentChannel::Mtn);
        $this->assertSame(3, $product->fresh()->quantity);

        $escrow = app(EscrowService::class);
        $escrow->raiseDispute($order->fresh()->load('items'), $customer, 'Item not as described');
        $this->assertSame(OrderStatus::Disputed, $order->fresh()->status);

        $escrow->resolveRefund($order->fresh()->load('items'), $admin);
        $this->assertSame(OrderStatus::Refunded, $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->quantity, 'Refund should restore stock.');
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PendingPayment->value]);

        $this->expectException(\RuntimeException::class);
        app(EscrowService::class)->confirmDelivery($order, $this->customer());
    }
}
