<?php

namespace Src\Domain\Catalog;

use App\Enums\ProductStatus;
use App\Models\OperatorProfile;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * ProductService
 * --------------
 * Owns catalogue write operations for an operator: creating and updating items,
 * attaching images, and moving items through their publish lifecycle. Keeping
 * this logic here (rather than in the controller) means slug generation, stock
 * derived status and media handling behave identically wherever items are
 * created — controllers, seeders or future imports.
 */
class ProductService
{
    /**
     * Create a new catalogue item for an operator.
     *
     * @param  array{name:string,description?:?string,price:float|string,currency?:string,quantity?:?int,business_category_id?:?int,status?:string}  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function create(OperatorProfile $operator, array $data, array $images = []): Product
    {
        $status = ProductStatus::from($data['status'] ?? ProductStatus::Draft->value);

        $product = new Product([
            'operator_profile_id' => $operator->getKey(),
            'business_category_id' => $data['business_category_id'] ?? $operator->business_category_id,
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($operator, $data['name']),
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'currency' => $data['currency'] ?? config('payment.currency', 'GHS'),
            'quantity' => $data['quantity'] ?? null,
        ]);

        $this->applyStatus($product, $status);
        $product->save();

        $this->attachImages($product, $images);

        return $product;
    }

    /**
     * Update an existing item. Only supplied keys are changed.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function update(Product $product, array $data, array $images = []): Product
    {
        if (array_key_exists('name', $data) && $data['name'] !== $product->name) {
            $product->name = $data['name'];
            $product->slug = $this->uniqueSlug($product->operator, $data['name'], $product);
        }

        foreach (['description', 'price', 'currency', 'quantity', 'business_category_id'] as $key) {
            if (array_key_exists($key, $data)) {
                $product->{$key} = $data[$key];
            }
        }

        if (array_key_exists('status', $data)) {
            $this->applyStatus($product, ProductStatus::from($data['status']));
        } else {
            // Keep the sold-out/active flag honest if stock changed.
            $this->reconcileStockStatus($product);
        }

        $product->save();

        $this->attachImages($product, $images);

        return $product;
    }

    /** Publish an item so it appears in the public store. */
    public function publish(Product $product): Product
    {
        $product->status = $product->quantity === 0 ? ProductStatus::SoldOut : ProductStatus::Active;
        $product->published_at ??= now();
        $product->save();

        return $product;
    }

    /** Remove an item from the public store without deleting it. */
    public function unpublish(Product $product): Product
    {
        $product->status = ProductStatus::Draft;
        $product->save();

        return $product;
    }

    /* ----------------------------------------------------------------------
     | Internals
     * --------------------------------------------------------------------*/

    private function applyStatus(Product $product, ProductStatus $status): void
    {
        $product->status = $status;

        if ($status->isPublicallyVisible()) {
            $product->published_at ??= now();
        }

        $this->reconcileStockStatus($product);
    }

    /** Flip Active <-> SoldOut based on remaining quantity, leaving other states alone. */
    private function reconcileStockStatus(Product $product): void
    {
        if ($product->quantity === null) {
            return;
        }

        if ($product->quantity === 0 && $product->status === ProductStatus::Active) {
            $product->status = ProductStatus::SoldOut;
        } elseif ($product->quantity > 0 && $product->status === ProductStatus::SoldOut) {
            $product->status = ProductStatus::Active;
        }
    }

    /**
     * @param  array<int, UploadedFile>  $images
     */
    private function attachImages(Product $product, array $images): void
    {
        foreach ($images as $image) {
            if ($image instanceof UploadedFile) {
                $product->addMedia($image)
                    ->usingFileName($this->safeFileName($image))
                    ->toMediaCollection('images');
            }
        }
    }

    /**
     * Build a whitespace-free, collision-proof stored file name. The original
     * base name is slugified (so "2026-06-29 at 5.28.17 AM.jpg" becomes
     * "2026-06-29-at-5-28-17-am-<uid>.jpg") and suffixed with a short unique id
     * so two uploads of the same file never overwrite one another.
     */
    private function safeFileName(UploadedFile $image): string
    {
        $extension = strtolower($image->getClientOriginalExtension() ?: $image->extension() ?: 'jpg');
        $base = Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'image';

        return $base.'-'.Str::lower(Str::random(8)).'.'.$extension;
    }

    /** Build a slug unique within the operator's own catalogue. */
    private function uniqueSlug(OperatorProfile $operator, string $name, ?Product $ignore = null): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while ($this->slugTaken($operator, $slug, $ignore)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function slugTaken(OperatorProfile $operator, string $slug, ?Product $ignore): bool
    {
        return Product::withTrashed()
            ->where('operator_profile_id', $operator->getKey())
            ->where('slug', $slug)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->exists();
    }
}
