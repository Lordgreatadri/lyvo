<?php

namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use App\Models\OperatorProfile;
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
            'categories'  => BusinessCategory::query()
                ->orderBy('name')
                ->withCount(['operatorProfiles as operators_count' => fn ($q) => $q->where('verification_status', \App\Enums\OperatorVerificationStatus::Approved->value)])
                ->get(),
            'operators'   => OperatorProfile::approved()
                ->with('category:id,name,slug')
                ->withCount(['products as published_products_count' => fn ($q) => $q->published()])
                ->orderByDesc('trust_score')
                ->take(6)
                ->get(),
            'pipeline'    => DemoData::escrowPipeline(),
            'trustLevels' => DemoData::trustLevels(),
        ]);
    }
}
