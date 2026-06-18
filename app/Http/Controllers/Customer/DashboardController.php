<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Support\DemoData;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('customer.dashboard', [
            'metrics'      => DemoData::metrics()['customer'],
            'transactions' => DemoData::escrowTransactions(),
            'operators'    => DemoData::operators(),
        ]);
    }
}
