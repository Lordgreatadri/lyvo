<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DemoData;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'metrics' => DemoData::metrics()['admin'],
            'queue'   => DemoData::verificationQueue(),
        ]);
    }

    public function verification(): View
    {
        return view('admin.verification', [
            'queue' => DemoData::verificationQueue(),
        ]);
    }
}
