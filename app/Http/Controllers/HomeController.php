<?php

namespace App\Http\Controllers;

use App\Support\DemoData;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * LYVO landing page.
     */
    public function index(): View
    {
        return view('home', [
            'categories'  => DemoData::categories(),
            'operators'   => DemoData::operators(),
            'pipeline'    => DemoData::escrowPipeline(),
            'trustLevels' => DemoData::trustLevels(),
        ]);
    }
}
