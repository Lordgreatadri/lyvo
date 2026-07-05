<?php

namespace Src\Domain\Commerce;

use App\Enums\OrderStatus;
use App\Models\EscrowEvent;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderUpdateNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * EscrowService
 * -------------
 * The escrow state machine. Every transition on an order flows through here so
 * the rules (which status may move to which), the audit trail (escrow_events)
 * and the buyer/seller notifications (email + SMS) are applied in exactly one
 * place. Each transition is wrapped in a DB transaction so the status change and
 * its audit event are atomic.
 *
 * Allowed transitions:
 *   PendingPayment → FundsHeld   (payment settled)
 *   PendingPayment → Cancelled   (payment failed)
 *   FundsHeld      → Processing  (operator accepts)
 *   Processing     → Delivered   (operator ships)
 *   Delivered      → Released    (buyer confirms → funds to operator)
 *   {FundsHeld,Processing,Delivered} → Disputed (buyer raises)
 *   Disputed       → Released | Refunded (admin resolves)
 */
class EscrowService
{
    /** Payment settled: hold the funds and reserve stock. */
    public function markFundsHeld(Order $order): Order
    {
        $this->assert($order, [OrderStatus::PendingPayment], OrderStatus::FundsHeld);

        return $this->transition($order, OrderStatus::FundsHeld, null, 'Payment received — funds held in escrow.', function (Order $order) {
            $order->funds_held_at = now();
            $this->reserveStock($order);
        }, notifyBoth: true);
    }

    /** Payment failed: cancel the pending order. */
    public function markPaymentFailed(Order $order): Order
    {
        if ($order->status !== OrderStatus::PendingPayment) {
            return $order;
        }

        return $this->transition($order, OrderStatus::Cancelled, null, 'Payment failed — order cancelled.', notifyCustomer: true);
    }

    public function markProcessing(Order $order, User $actor): Order
    {
        $this->assert($order, [OrderStatus::FundsHeld], OrderStatus::Processing);

        return $this->transition($order, OrderStatus::Processing, $actor, 'Seller is preparing your order.', function (Order $order) {
            $order->processing_at = now();
        }, notifyCustomer: true);
    }

    public function markDelivered(Order $order, User $actor): Order
    {
        $this->assert($order, [OrderStatus::Processing, OrderStatus::FundsHeld], OrderStatus::Delivered);

        return $this->transition($order, OrderStatus::Delivered, $actor, 'Order marked as delivered — awaiting buyer confirmation.', function (Order $order) {
            $order->delivered_at = now();
        }, notifyCustomer: true);
    }

    /** Buyer confirms delivery → funds released to the operator. */
    public function confirmDelivery(Order $order, User $actor): Order
    {
        $this->assert($order, [OrderStatus::Delivered], OrderStatus::Released);

        return $this->transition($order, OrderStatus::Released, $actor, 'Delivery confirmed — funds released to the seller.', function (Order $order) {
            $order->released_at = now();
        }, notifyOperator: true);
    }

    public function raiseDispute(Order $order, User $actor, ?string $reason = null): Order
    {
        $this->assert($order, [OrderStatus::FundsHeld, OrderStatus::Processing, OrderStatus::Delivered], OrderStatus::Disputed);

        return $this->transition($order, OrderStatus::Disputed, $actor, $reason ?: 'Buyer raised a dispute.', function (Order $order) {
            $order->disputed_at = now();
        }, notifyBoth: true);
    }

    /** Admin resolves a dispute in the operator's favour. */
    public function resolveRelease(Order $order, User $admin, ?string $note = null): Order
    {
        $this->assert($order, [OrderStatus::Disputed], OrderStatus::Released);

        return $this->transition($order, OrderStatus::Released, $admin, $note ?: 'Dispute resolved — funds released to the seller.', function (Order $order) {
            $order->released_at = now();
        }, notifyBoth: true);
    }

    /** Admin resolves a dispute in the buyer's favour (refund + restock). */
    public function resolveRefund(Order $order, User $admin, ?string $note = null): Order
    {
        $this->assert($order, [OrderStatus::Disputed], OrderStatus::Refunded);

        return $this->transition($order, OrderStatus::Refunded, $admin, $note ?: 'Dispute resolved — buyer refunded.', function (Order $order) {
            $this->restock($order);
        }, notifyBoth: true);
    }

    /* ----------------------------------------------------------------------
     | Internals
     * --------------------------------------------------------------------*/

    /**
     * @param  array<int, OrderStatus>  $allowedFrom
     */
    private function assert(Order $order, array $allowedFrom, OrderStatus $to): void
    {
        if (! in_array($order->status, $allowedFrom, true)) {
            throw new RuntimeException(sprintf(
                'Cannot move order %s from %s to %s.',
                $order->order_number,
                $order->status->value,
                $to->value,
            ));
        }
    }

    /**
     * Apply a status transition atomically: mutate the row, persist, write the
     * audit event, then notify the relevant parties.
     */
    private function transition(
        Order $order,
        OrderStatus $to,
        ?User $actor,
        string $note,
        ?callable $mutator = null,
        bool $notifyCustomer = false,
        bool $notifyOperator = false,
        bool $notifyBoth = false,
    ): Order {
        $from = $order->status;

        DB::transaction(function () use ($order, $to, $actor, $note, $mutator, $from) {
            if ($mutator) {
                $mutator($order);
            }

            $order->status = $to;
            $order->save();

            EscrowEvent::create([
                'order_id' => $order->getKey(),
                'actor_id' => $actor?->getKey(),
                'from_status' => $from->value,
                'to_status' => $to->value,
                'note' => $note,
            ]);
        });

        $this->notify($order, $note, $notifyBoth || $notifyCustomer, $notifyBoth || $notifyOperator);

        return $order;
    }

    private function reserveStock(Order $order): void
    {
        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            // Services carry a NULL quantity (unlimited) — skip the decrement and
            // only record the sale. For tracked stock, decrement atomically and
            // only when enough remains so an unsigned column cannot underflow.
            $item->product()
                ->whereNotNull('quantity')
                ->where('quantity', '>=', $item->quantity)
                ->decrement('quantity', $item->quantity);

            $item->product()->increment('sold_count', $item->quantity);
        }
    }

    private function restock(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product_id) {
                $item->product()->increment('quantity', $item->quantity);
            }
        }
    }

    /** Send the buyer and/or seller an email + SMS about the change. */
    private function notify(Order $order, string $message, bool $customer, bool $operator): void
    {
        $headline = $order->status->label();

        try {
            if ($customer && $order->customer) {
                $order->customer->notify(new OrderUpdateNotification($order, $headline, $message));
                if ($order->customer->phone) {
                    send_sms($order->customer->phone, "LYVO {$order->order_number}: {$message}", 'order', $order->customer->id);
                }
            }

            if ($operator && ($seller = $order->operator?->user)) {
                $seller->notify(new OrderUpdateNotification($order, $headline, $message));
                if ($seller->phone) {
                    send_sms($seller->phone, "LYVO {$order->order_number}: {$message}", 'order', $seller->id);
                }
            }
        } catch (\Throwable $e) {
            // Notifications must never break an escrow transition.
            Log::warning('Order notification failed', ['order' => $order->order_number, 'error' => $e->getMessage()]);
        }
    }
}
