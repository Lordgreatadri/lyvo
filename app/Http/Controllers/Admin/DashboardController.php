<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OperatorVerificationStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\OperatorProfile;
use App\Models\Order;
use Illuminate\View\View;
use Src\Domain\Payment\Reporting\PaymentOverview;

class DashboardController extends Controller
{
    public function index(PaymentOverview $payments): View
    {
        $orders = Order::get(['status', 'total']);
        $held = $orders->filter(fn ($o) => $o->status->isEscrowHeld())->sum('total');
        $released = $orders->where('status', OrderStatus::Released)->sum('total');
        $disputes = $orders->where('status', OrderStatus::Disputed)->count();

        $pendingReviews = OperatorProfile::whereIn('verification_status', [
            OperatorVerificationStatus::Pending->value,
            OperatorVerificationStatus::InReview->value,
        ])->count();

        $verified = OperatorProfile::where('verification_status', OperatorVerificationStatus::Approved->value)->count();

        return view('admin.dashboard', [
            'metrics'  => [
                ['label' => 'Pending reviews',    'value' => (string) $pendingReviews,                 'delta' => 'In queue',          'icon' => 'clipboard'],
                ['label' => 'Verified operators', 'value' => number_format($verified),                 'delta' => 'Approved',          'icon' => 'badge'],
                ['label' => 'Held in escrow',     'value' => 'GH₵ '.number_format((float) $held, 2),   'delta' => 'Across platform',   'icon' => 'shield'],
                ['label' => 'Open disputes',      'value' => (string) $disputes,                       'delta' => 'Awaiting resolution','icon' => 'flag'],
            ],
            'escrow'   => [
                'held'     => $held,
                'released' => $released,
                'disputes' => $disputes,
            ],
            'queue'    => OperatorProfile::with(['user:id,name', 'category:id,name'])
                ->whereIn('verification_status', [OperatorVerificationStatus::Pending->value, OperatorVerificationStatus::InReview->value])
                ->latest()->limit(5)->get(),
            'disputed' => Order::where('status', OrderStatus::Disputed->value)
                ->with(['customer:id,name', 'operator:id,uuid,business_name'])->latest()->limit(5)->get(),
            'payments' => $payments->forAdmin(),
        ]);
    }

    public function verification(): View
    {
        return view('admin.verification', [
            'queue' => OperatorProfile::with(['user:id,name,email', 'category:id,name', 'media'])
                ->whereIn('verification_status', [
                    OperatorVerificationStatus::Pending->value,
                    OperatorVerificationStatus::InReview->value,
                ])
                ->latest()
                ->get(),
        ]);
    }
}
