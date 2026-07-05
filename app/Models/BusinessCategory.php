<?php

namespace App\Models;

use Dyrynda\Database\Support\BindsOnUuid;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * BusinessCategory
 * ----------------
 * Reference list of operator business categories used by the directory filters
 * and the operator onboarding form.
 */
class BusinessCategory extends Model
{
    use BindsOnUuid, GeneratesUuid, HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'uuid' => 'string',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function operatorProfiles(): HasMany
    {
        return $this->hasMany(OperatorProfile::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
