<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Support\DemoData;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('operator.dashboard', [
            'metrics'      => DemoData::metrics()['operator'],
            'transactions' => DemoData::escrowTransactions(),
            'products'     => DemoData::products(),
        ]);
    }

    public function verification(): View
    {
        return view('operator.verification', [
            'steps' => DemoData::verificationSteps(),
        ]);
    }

    /**
     * Operator onboarding wizard (business info, Ghana Card, video, status).
     */
    public function register(): View
    {
        return view('operator.register');
    }
}
