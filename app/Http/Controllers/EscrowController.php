<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * EscrowController
 * ----------------
 * The role-aware escrow desk backed by real orders. Customers see the orders
 * whose funds they have protected, operators see funds awaiting release, and
 * admins see everything currently held on the platform. Each row links to the
 * order's escrow timeline.
 */
class EscrowController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isOperator()) {
            $role = 'operator';
            $orders = Order::forOperator($user->operatorProfile->id)->escrowHeld()
                ->with(['customer:id,name', 'items'])->latest()->paginate(12);
        } elseif ($user->isAdmin()) {
            $role = 'admin';
            $orders = Order::escrowHeld()
                ->with(['customer:id,name', 'operator:id,uuid,business_name', 'items'])->latest()->paginate(15);
        } else {
            $role = 'customer';
            $orders = Order::forCustomer($user->id)->escrowHeld()
                ->with(['operator:id,uuid,business_name', 'items'])->latest()->paginate(12);
        }

        return view('orders.index', [
            'role' => $role,
            'orders' => $orders,
            'escrow' => true,
            'summary' => null,
        ]);
    }

    public function show(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();

        $route = match (true) {
            $user->isOperator() && $order->operator_profile_id === $user->operatorProfile?->id => 'operator.orders.show',
            $user->isAdmin() => 'admin.orders.show',
            $order->customer_id === $user->id => 'customer.orders.show',
            default => null,
        };

        if ($route === null) {
            throw new NotFoundHttpException('Order not found.');
        }

        return redirect()->route($route, $order);
    }
}
