<?php

namespace App\Http\Controllers\Operator;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Support\DemoData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->operatorProfile;
        $profileId = $profile->id;

        $orders = Order::forOperator($profileId)->get(['status', 'total']);
        $inEscrow = $orders->filter(fn ($o) => $o->status->isEscrowHeld())->sum('total');
        $released = $orders->where('status', OrderStatus::Released)->sum('total');
        $activeCount = $orders->filter(fn ($o) => $o->status->isEscrowHeld())->count();

        $customerCount = Order::forOperator($profileId)->distinct('customer_id')->count('customer_id');

        return view('operator.dashboard', [
            'profile'  => $profile,
            'metrics'  => [
                ['label' => 'Total orders',   'value' => (string) $orders->count(),                       'delta' => $activeCount.' active',      'icon' => 'box'],
                ['label' => 'Funds in escrow','value' => 'GH₵ '.number_format((float) $inEscrow, 2),      'delta' => 'Awaiting release',          'icon' => 'shield'],
                ['label' => 'Released',       'value' => 'GH₵ '.number_format((float) $released, 2),       'delta' => 'Paid out',                  'icon' => 'wallet'],
                ['label' => 'Customers',      'value' => (string) $customerCount,                          'delta' => 'Lifetime',                  'icon' => 'users'],
            ],
            'recent'   => Order::forOperator($profileId)->with(['customer:id,name', 'items'])->latest()->limit(5)->get(),
            'products' => Product::where('operator_profile_id', $profileId)->latest()->limit(3)->get(),
            'productCount' => Product::where('operator_profile_id', $profileId)->count(),
        ]);
    }

    public function verification(): View
    {
        return view('operator.verification', [
            'steps' => DemoData::verificationSteps(),
        ]);
    }

    /**
     * Verification status page shown while an operator awaits (or has been
     * declined) admin approval. The operator dashboard stays locked until the
     * profile's verification_status is Approved.
     */
    public function pending(Request $request): View
    {
        return view('operator.pending', [
            'profile' => $request->user()->operatorProfile,
        ]);
    }
}
