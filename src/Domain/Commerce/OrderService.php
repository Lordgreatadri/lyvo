<?php

namespace Src\Domain\Commerce;

use App\Enums\OrderStatus;
use App\Enums\PaymentChannel;
use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Src\Domain\Payment\PaymentService;

/**
 * OrderService
 * ------------
 * Places escrow-protected orders. Creating an order and starting its payment are
 * one atomic operation: the order row + line items are written in a transaction,
 * then a PaymentTransaction is opened against the order (payable morph). The
 * order stays PendingPayment until the gateway settles — at which point the
 * PaymentSettled event advances it to FundsHeld (see EscrowService). In local
 * dev the network-free `log` gateway settles instantly so the full escrow flow
 * can be walked end-to-end without a real payment.
 */
class OrderService
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    /**
     * Place a single-item escrow order and begin its payment.
     */
    public function place(
        User $customer,
        Product $product,
        int $quantity,
        PaymentChannel $channel,
        ?string $payerPhone = null,
        ?DeliveryAddress $address = null,
        ?string $note = null,
    ): Order {
        if (! $product->status->isBuyable()) {
            throw new RuntimeException('This item is not available for purchase.');
        }

        $quantity = max(1, $quantity);

        if ($product->quantity !== null && $product->quantity < $quantity) {
            throw new RuntimeException('Not enough stock for this item.');
        }

        $unitPrice = (float) $product->price;
        $subtotal = round($unitPrice * $quantity, 2);

        $order = DB::transaction(function () use ($customer, $product, $quantity, $unitPrice, $subtotal, $address, $note) {
            $order = Order::create([
                'order_number' => $this->orderNumber(),
                'customer_id' => $customer->getKey(),
                'operator_profile_id' => $product->operator_profile_id,
                'status' => OrderStatus::PendingPayment,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'currency' => $product->currency,
                'delivery_recipient' => $address?->recipient_name ?? $customer->name,
                'delivery_phone' => $address?->phone ?? $customer->phone,
                'delivery_address' => $address ? $this->formatAddress($address) : null,
                'delivery_note' => $note,
                'placed_at' => now(),
            ]);

            $order->items()->create([
                'product_id' => $product->getKey(),
                'name' => $product->name,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => $subtotal,
            ]);

            return $order;
        });

        $transaction = $this->payments->charge(
            amount: $subtotal,
            payer: $payerPhone ?: (string) ($customer->phone ?? ''),
            channel: $channel,
            context: 'order',
            userId: $customer->getKey(),
            reference: $order->order_number,
            payable: $order,
        );

        $order->forceFill(['payment_transaction_id' => $transaction->getKey()])->save();

        // Local/dev network-free gateway settles synchronously so the escrow
        // lifecycle is walkable without a live payment/webhook.
        if ($this->payments->providerName() === 'log') {
            $this->payments->syncStatus($transaction);
        }

        return $order->refresh();
    }

    /** Human-friendly, collision-checked order reference. */
    private function orderNumber(): string
    {
        do {
            $number = 'LYV-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }

    private function formatAddress(DeliveryAddress $address): string
    {
        return collect([
            $address->address_line,
            $address->area,
            $address->city,
            $address->region,
        ])->filter()->implode(', ');
    }
}
