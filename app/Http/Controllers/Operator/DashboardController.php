<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Support\DemoData;
use Illuminate\Http\Request;
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
