<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DemoData;
use Illuminate\View\View;
use Src\Domain\Payment\Reporting\PaymentOverview;

class DashboardController extends Controller
{
    public function index(PaymentOverview $payments): View
    {
        return view('admin.dashboard', [
            'metrics'  => DemoData::metrics()['admin'],
            'queue'    => DemoData::verificationQueue(),
            'payments' => $payments->forAdmin(),
        ]);
    }

    public function verification(): View
    {
        return view('admin.verification', [
            'queue' => DemoData::verificationQueue(),
        ]);
    }
}
