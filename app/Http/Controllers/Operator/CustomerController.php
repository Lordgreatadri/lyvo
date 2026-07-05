<?php

namespace App\Http\Controllers\Operator;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Operator CustomerController
 * ---------------------------
 * A roll-up of the buyers an operator has served — order counts, lifetime value
 * and their most recent order — built entirely from real order data.
 */
class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $profileId = $request->user()->operatorProfile->id;

        $customers = Order::forOperator($profileId)
            ->with('customer:id,uuid,name,email')
            ->get()
            ->groupBy('customer_id')
            ->map(function ($orders) {
                $first = $orders->first();

                return [
                    'customer' => $first->customer,
                    'orders' => $orders->count(),
                    'spent' => $orders->whereIn('status', [
                        OrderStatus::Released,
                        OrderStatus::FundsHeld,
                        OrderStatus::Processing,
                        OrderStatus::Delivered,
                    ])->sum('total'),
                    'last_order' => $orders->max('created_at'),
                ];
            })
            ->sortByDesc('spent')
            ->values();

        return view('operator.customers.index', [
            'customers' => $customers,
        ]);
    }
}
