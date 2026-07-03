<?php

namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use App\Models\OperatorProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectoryController extends Controller
{
    /**
     * Verified operator directory (real, approved operators only).
     */
    public function index(Request $request): View
    {
        $category = $request->query('category');

        $operators = OperatorProfile::approved()
            ->with('category:id,name,slug')
            ->withCount(['products as published_products_count' => fn ($q) => $q->published()])
            ->when($category, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $category)))
            ->orderByDesc('trust_score')
            ->paginate(12)
            ->withQueryString();

        return view('directory.index', [
            'categories'     => BusinessCategory::orderBy('name')->get(),
            'operators'      => $operators,
            'activeCategory' => $category,
        ]);
    }

    /**
     * Operator profile page, resolved by UUID (approved operators only).
     */
    public function show(OperatorProfile $operator): View
    {
        abort_unless($operator->isApproved(), 404);

        $operator->load('category:id,name,slug', 'user:id,name');

        $products = $operator->products()->published()->storeOrdered()->get();

        return view('directory.show', [
            'operator' => $operator,
            'products' => $products,
        ]);
    }
}
