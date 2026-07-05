<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Src\Domain\Commerce\EscrowService;

/**
 * Operator OrderController
 * ------------------------
 * The seller's fulfilment queue: accept a paid order (start processing) and mark
 * it delivered. Funds stay in escrow until the buyer confirms.
 */
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $profileId = $request->user()->operatorProfile->id;

        $orders = Order::forOperator($profileId)
            ->with(['customer:id,name', 'items'])
            ->latest()
            ->paginate(12);

        return view('orders.index', [
            'role' => 'operator',
            'orders' => $orders,
            'summary' => $this->summary($profileId),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorizeOrder($request, $order);

        $order->load(['customer:id,name,phone', 'items.product', 'events.actor', 'payment']);

        return view('orders.show', [
            'role' => 'operator',
            'order' => $order,
        ]);
    }

    public function processing(Request $request, Order $order, EscrowService $escrow): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        try {
            $escrow->markProcessing($order, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Order marked as processing.');
    }

    public function delivered(Request $request, Order $order, EscrowService $escrow): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        try {
            $escrow->markDelivered($order, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Order marked as delivered — awaiting buyer confirmation.');
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        if ($order->operator_profile_id !== $request->user()->operatorProfile?->id) {
            throw new NotFoundHttpException('Order not found.');
        }
    }

    /** @return array<string, mixed> */
    private function summary(int $profileId): array
    {
        $orders = Order::forOperator($profileId)->get(['status', 'total']);

        return [
            'active' => $orders->filter(fn ($o) => $o->status->isEscrowHeld())->count(),
            'in_escrow' => $orders->filter(fn ($o) => $o->status->isEscrowHeld())->sum('total'),
            'released' => $orders->where('status', \App\Enums\OrderStatus::Released)->sum('total'),
            'total' => $orders->count(),
        ];
    }
}
