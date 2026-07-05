<?php

namespace App\Listeners;

use App\Enums\PaymentStatus;
use App\Events\PaymentSettled;
use App\Models\Order;
use Src\Domain\Commerce\EscrowService;

/**
 * AdvanceEscrowOnPayment
 * ----------------------
 * Bridges the Payment domain to the escrow lifecycle. When a transaction that
 * belongs to an Order settles, this advances the order: a successful payment
 * moves it to FundsHeld (escrow), a failed payment cancels it. Keeping this in a
 * listener means the Payment domain stays decoupled from orders.
 */
class AdvanceEscrowOnPayment
{
    public function __construct(private readonly EscrowService $escrow)
    {
    }

    public function handle(PaymentSettled $event): void
    {
        $payable = $event->transaction->payable;

        if (! $payable instanceof Order) {
            return;
        }

        match ($event->transaction->status) {
            PaymentStatus::Successful => $this->hold($payable),
            PaymentStatus::Failed => $this->escrow->markPaymentFailed($payable->fresh()),
            default => null,
        };
    }

    private function hold(Order $order): void
    {
        $order = $order->fresh();

        if ($order->status === \App\Enums\OrderStatus::PendingPayment) {
            $order->load('items');
            $this->escrow->markFundsHeld($order);
        }
    }
}
