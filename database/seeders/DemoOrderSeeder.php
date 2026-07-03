<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\EscrowEvent;
use App\Models\Order;
use App\Models\OperatorProfile;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * DemoOrderSeeder
 * ---------------
 * Creates a spread of escrow orders between the demo customer and operator so
 * every dashboard (customer, operator, admin) and the escrow desk have real
 * lifecycle data to render. Idempotent: keyed on order_number.
 */
class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('email', 'customer@lyvo.test')->first();
        $operator = User::where('email', 'operator@lyvo.test')->first()?->operatorProfile;

        if (! $customer || ! $operator instanceof OperatorProfile) {
            return;
        }

        $products = Product::where('operator_profile_id', $operator->id)
            ->orderBy('id')
            ->get();

        if ($products->isEmpty()) {
            return;
        }

        $blueprints = [
            ['number' => 'LYV-DEMO-01', 'status' => OrderStatus::FundsHeld, 'qty' => 1, 'days' => 1],
            ['number' => 'LYV-DEMO-02', 'status' => OrderStatus::Processing, 'qty' => 2, 'days' => 3],
            ['number' => 'LYV-DEMO-03', 'status' => OrderStatus::Delivered, 'qty' => 1, 'days' => 6],
            ['number' => 'LYV-DEMO-04', 'status' => OrderStatus::Released, 'qty' => 1, 'days' => 12],
            ['number' => 'LYV-DEMO-05', 'status' => OrderStatus::Disputed, 'qty' => 3, 'days' => 4],
        ];

        foreach ($blueprints as $i => $bp) {
            $product = $products[$i % $products->count()];
            $unit = (float) $product->price;
            $subtotal = $unit * $bp['qty'];
            $placedAt = now()->subDays($bp['days']);

            $order = Order::updateOrCreate(
                ['order_number' => $bp['number']],
                [
                    'customer_id' => $customer->id,
                    'operator_profile_id' => $operator->id,
                    'status' => $bp['status'],
                    'subtotal' => $subtotal,
                    'total' => $subtotal,
                    'currency' => 'GHS',
                    'delivery_recipient' => $customer->name,
                    'delivery_phone' => $customer->phone ?? '0244000000',
                    'delivery_address' => 'Demo delivery address, Accra',
                    'delivery_note' => null,
                    'placed_at' => $placedAt,
                    'funds_held_at' => $placedAt->copy()->addMinutes(2),
                    'processing_at' => $bp['status'] === OrderStatus::FundsHeld ? null : $placedAt->copy()->addHours(4),
                    'delivered_at' => in_array($bp['status'], [OrderStatus::Delivered, OrderStatus::Released], true) ? $placedAt->copy()->addDays(2) : null,
                    'released_at' => $bp['status'] === OrderStatus::Released ? $placedAt->copy()->addDays(3) : null,
                    'disputed_at' => $bp['status'] === OrderStatus::Disputed ? $placedAt->copy()->addDays(1) : null,
                ],
            );

            $order->items()->delete();
            $order->items()->create([
                'product_id' => $product->id,
                'name' => $product->name,
                'unit_price' => $unit,
                'quantity' => $bp['qty'],
                'line_total' => $subtotal,
            ]);

            // Rebuild a simple audit trail matching the lifecycle reached.
            $order->events()->delete();
            $trail = [[null, OrderStatus::PendingPayment], [OrderStatus::PendingPayment, OrderStatus::FundsHeld]];

            if (in_array($bp['status'], [OrderStatus::Processing, OrderStatus::Delivered, OrderStatus::Released], true)) {
                $trail[] = [OrderStatus::FundsHeld, OrderStatus::Processing];
            }
            if (in_array($bp['status'], [OrderStatus::Delivered, OrderStatus::Released], true)) {
                $trail[] = [OrderStatus::Processing, OrderStatus::Delivered];
            }
            if ($bp['status'] === OrderStatus::Released) {
                $trail[] = [OrderStatus::Delivered, OrderStatus::Released];
            }
            if ($bp['status'] === OrderStatus::Disputed) {
                $trail[] = [OrderStatus::FundsHeld, OrderStatus::Disputed];
            }

            foreach ($trail as $step) {
                EscrowEvent::create([
                    'order_id' => $order->id,
                    'actor_id' => null,
                    'from_status' => $step[0],
                    'to_status' => $step[1],
                    'note' => 'Demo lifecycle event.',
                ]);
            }
        }
    }
}
