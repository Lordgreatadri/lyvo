<?php

namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * StoreController
 * ---------------
 * The public marketplace. Lists published items from approved operators with
 * paid (boosted) items surfaced first, and renders individual item pages.
 *
 * Every query is index-backed: the listing filters on `status` + `published_at`
 * and orders by `boost_weight` + `published_at` (both indexed), eager-loads the
 * operator, category and media to avoid N+1, and paginates.
 */
class StoreController extends Controller
{
    public function index(Request $request): View
    {
        $categorySlug = $request->query('category');

        $products = Product::query()
            ->published()
            ->when($categorySlug, function ($query) use ($categorySlug) {
                $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
            })
            ->with(['operator:id,uuid,business_name,verification_status', 'category:id,name,slug', 'media'])
            ->storeOrdered()
            ->paginate(12)
            ->withQueryString();

        return view('store.index', [
            'products' => $products,
            'categories' => BusinessCategory::query()->active()->orderBy('name')->get(),
            'activeCategory' => $categorySlug,
        ]);
    }

    public function show(Product $product): View
    {
        if (! $product->isPublished()) {
            throw new NotFoundHttpException('Item not available.');
        }

        $product->load(['operator', 'category', 'media']);

        // Cheap, non-blocking popularity counter (no model events / timestamps touched).
        Product::whereKey($product->getKey())->increment('views');

        $related = Product::query()
            ->published()
            ->where('business_category_id', $product->business_category_id)
            ->whereKeyNot($product->getKey())
            ->with('media')
            ->storeOrdered()
            ->limit(4)
            ->get();

        return view('store.show', [
            'product' => $product,
            'related' => $related,
        ]);
    }
}
