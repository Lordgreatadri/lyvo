<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Src\Domain\Commerce\EscrowService;

/**
 * Admin OrderController
 * ---------------------
 * Platform-wide escrow oversight: every order, live disputes, and the power to
 * resolve a dispute by releasing funds to the seller or refunding the buyer.
 */
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter');

        $query = Order::with(['customer:id,name', 'operator:id,uuid,business_name', 'items'])->latest();

        if ($filter === 'disputed') {
            $query->where('status', OrderStatus::Disputed->value);
        } elseif ($filter === 'escrow') {
            $query->escrowHeld();
        }

        return view('orders.index', [
            'role' => 'admin',
            'orders' => $query->paginate(15)->withQueryString(),
            'summary' => $this->summary(),
            'filter' => $filter,
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['customer:id,name,phone', 'operator:id,uuid,business_name', 'items.product', 'events.actor', 'payment']);

        return view('orders.show', [
            'role' => 'admin',
            'order' => $order,
        ]);
    }

    public function release(Request $request, Order $order, EscrowService $escrow): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        try {
            $escrow->resolveRelease($order, $request->user(), $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Dispute resolved — funds released to the seller.');
    }

    public function refund(Request $request, Order $order, EscrowService $escrow): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        try {
            $escrow->resolveRefund($order, $request->user(), $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Dispute resolved — buyer refunded.');
    }

    /** @return array<string, mixed> */
    private function summary(): array
    {
        $orders = Order::get(['status', 'total']);

        return [
            'in_escrow' => $orders->filter(fn ($o) => $o->status->isEscrowHeld())->sum('total'),
            'disputes' => $orders->where('status', OrderStatus::Disputed)->count(),
            'released' => $orders->where('status', OrderStatus::Released)->sum('total'),
            'total' => $orders->count(),
        ];
    }
}
