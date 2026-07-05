<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OperatorProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        $orders = Order::forCustomer($userId)->get(['status', 'total']);
        $active = $orders->filter(fn ($o) => $o->status->isEscrowHeld());
        $protected = $active->sum('total');

        // Operators this customer has bought from, most recent first.
        $operatorIds = Order::forCustomer($userId)->latest()->pluck('operator_profile_id')->unique()->take(3)->values();
        $operators = OperatorProfile::whereIn('id', $operatorIds)->get()
            ->sortBy(fn ($operator) => $operatorIds->search($operator->id))
            ->values();

        return view('customer.dashboard', [
            'metrics' => [
                ['label' => 'Active escrows', 'value' => (string) $active->count(),                  'delta' => 'In progress', 'icon' => 'shield'],
                ['label' => 'Protected now',  'value' => 'GH₵ '.number_format((float) $protected, 2), 'delta' => 'Held safely', 'icon' => 'lock'],
                ['label' => 'Completed',      'value' => (string) $orders->where('status', OrderStatus::Released)->count(), 'delta' => 'Delivered', 'icon' => 'check-circle'],
                ['label' => 'Total orders',   'value' => (string) $orders->count(),                  'delta' => 'Lifetime',    'icon' => 'box'],
            ],
            'recent'    => Order::forCustomer($userId)->with(['operator:id,uuid,business_name', 'items'])->latest()->limit(5)->get(),
            'operators' => $operators,
        ]);
    }
}
