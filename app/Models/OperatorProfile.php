<?php

namespace App\Models;

use App\Enums\OperatorVerificationStatus;
use Dyrynda\Database\Support\BindsOnUuid;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * OperatorProfile
 * ---------------
 * One-to-one extension of an operator User. Owns the verification state machine
 * and (via Spatie media library) all uploaded identity/business assets.
 *
 * Media collections:
 *  - ghana_card_front   (single)
 *  - ghana_card_back    (single)
 *  - verification_video (single)
 *  - logo               (single)
 *  - cover              (single)
 */
class OperatorProfile extends Model implements HasMedia
{
    use BindsOnUuid, GeneratesUuid, HasFactory, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'business_category_id',
        'business_name',
        'owner_full_name',
        'business_location',
        'business_description',
        'ghana_card_number',
        'verification_status',
        'ghana_card_submitted_at',
        'video_submitted_at',
        'submitted_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'trust_score',
    ];

    protected $casts = [
        'uuid' => 'string',
        'verification_status' => OperatorVerificationStatus::class,
        'ghana_card_submitted_at' => 'datetime',
        'video_submitted_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'trust_score' => 'integer',
    ];

    /* ----------------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------------*/

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BusinessCategory::class, 'business_category_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function verificationEvents(): HasMany
    {
        return $this->hasMany(OperatorVerificationEvent::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'operator_profile_id');
    }

    /* ----------------------------------------------------------------------
     | Sensitive data — Ghana Card number encrypted at rest
     * --------------------------------------------------------------------*/

    protected function ghanaCardNumber(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => filled($value) ? Crypt::decryptString($value) : null,
            set: fn ($value) => filled($value) ? Crypt::encryptString($value) : null,
        );
    }

    /* ----------------------------------------------------------------------
     | Media
     * --------------------------------------------------------------------*/

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('ghana_card_front')->singleFile();
        $this->addMediaCollection('ghana_card_back')->singleFile();
        $this->addMediaCollection('verification_video')->singleFile();
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('cover')->singleFile();
    }

    /* ----------------------------------------------------------------------
     | Verification helpers
     * --------------------------------------------------------------------*/

    public function isApproved(): bool
    {
        return $this->verification_status === OperatorVerificationStatus::Approved;
    }

    public function scopeApproved($query)
    {
        return $query->where('verification_status', OperatorVerificationStatus::Approved->value);
    }
}
