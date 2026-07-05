<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Dyrynda\Database\Support\BindsOnUuid;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Product
 * -------
 * A catalogue item listed by an approved operator. Images live in the Spatie
 * media library ("images" collection). Public visibility is governed by
 * `status` + `published_at`; the `scopePublished` scope encapsulates that rule
 * so call sites never reconstruct it.
 *
 * Promotion boost columns (`boost_weight`, `boosted_until`, `is_featured`) are
 * denormalised for the hot "top of home" ordering and kept in sync by the
 * promotion domain (Phase 2B).
 *
 * @property string $uuid
 * @property ProductStatus $status
 * @property float $price
 */
class Product extends Model implements HasMedia
{
    use BindsOnUuid, GeneratesUuid, HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'uuid',
        'operator_profile_id',
        'business_category_id',
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'quantity',
        'status',
        'is_featured',
        'boost_weight',
        'boosted_until',
        'published_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'status' => ProductStatus::class,
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'is_featured' => 'boolean',
        'boost_weight' => 'integer',
        'views' => 'integer',
        'sold_count' => 'integer',
        'boosted_until' => 'datetime',
        'published_at' => 'datetime',
    ];

    /* ----------------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------------*/

    public function operator(): BelongsTo
    {
        return $this->belongsTo(OperatorProfile::class, 'operator_profile_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BusinessCategory::class, 'business_category_id');
    }

    /* ----------------------------------------------------------------------
     | Media
     * --------------------------------------------------------------------*/

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(400)
            ->nonQueued();
    }

    /* ----------------------------------------------------------------------
     | Scopes
     * --------------------------------------------------------------------*/

    /**
     * Items that may appear in the public store: publicly-visible status with a
     * publish time that has arrived.
     *
     * @param  Builder<Product>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereIn('status', [ProductStatus::Active->value, ProductStatus::SoldOut->value])
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Public store ordering: boosted (paid) items first, then most recent.
     *
     * @param  Builder<Product>  $query
     */
    public function scopeStoreOrdered(Builder $query): void
    {
        $query->orderByDesc('boost_weight')->orderByDesc('published_at');
    }

    /* ----------------------------------------------------------------------
     | Helpers
     * --------------------------------------------------------------------*/

    public function isPublished(): bool
    {
        return $this->status->isPublicallyVisible()
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    public function isInStock(): bool
    {
        return $this->quantity === null || $this->quantity > 0;
    }

    /** True while a paid promotion boost is currently active. */
    public function isBoosted(): bool
    {
        return $this->boost_weight > 0
            && $this->boosted_until !== null
            && $this->boosted_until->isFuture();
    }
}
