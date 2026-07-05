<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\ProductRequest;
use App\Models\BusinessCategory;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Src\Domain\Catalog\ProductService;

/**
 * Operator ProductController
 * --------------------------
 * The operator's catalogue management screen. Every action is authorized by
 * ProductPolicy (ownership + `products.manage` + approved-operator gate); the
 * route group already guarantees an authenticated, approved operator.
 */
class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products)
    {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request): View
    {
        $operator = $request->user()->operatorProfile;

        $products = Product::query()
            ->where('operator_profile_id', $operator->getKey())
            ->with(['category', 'media'])
            ->latest()
            ->paginate(12);

        return view('operator.products.index', [
            'products' => $products,
        ]);
    }

    public function create(): View
    {
        return view('operator.products.create', [
            'categories' => $this->categories(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = $this->products->create(
            $request->user()->operatorProfile,
            $request->validated(),
            $request->file('images', []),
        );

        return redirect()
            ->route('operator.products.edit', $product)
            ->with('status', 'Item created. Publish it when you are ready to sell.');
    }

    public function edit(Product $product): View
    {
        $product->load('media');

        return view('operator.products.edit', [
            'product' => $product,
            'categories' => $this->categories(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($product, $request->validated(), $request->file('images', []));

        return redirect()
            ->route('operator.products.edit', $product)
            ->with('status', 'Item updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()
            ->route('operator.products.index')
            ->with('status', 'Item removed from your catalogue.');
    }

    /** Publish the item into the public store. */
    public function publish(Product $product): RedirectResponse
    {
        $this->authorize('update', $product);
        $this->products->publish($product);

        return back()->with('status', 'Item is now live in the store.');
    }

    /** Hide the item from the public store. */
    public function unpublish(Product $product): RedirectResponse
    {
        $this->authorize('update', $product);
        $this->products->unpublish($product);

        return back()->with('status', 'Item hidden from the store.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, BusinessCategory>
     */
    private function categories()
    {
        return BusinessCategory::query()->active()->orderBy('name')->get();
    }
}
