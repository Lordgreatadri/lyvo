<?php

namespace App\Models;

use Dyrynda\Database\Support\BindsOnUuid;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CustomerProfile
 * ---------------
 * One-to-one extension of a customer User.
 */
class CustomerProfile extends Model
{
    use BindsOnUuid, GeneratesUuid;

    protected $fillable = [
        'user_id',
        'preferred_name',
        'date_of_birth',
        'marketing_opt_in',
    ];

    protected $casts = [
        'uuid' => 'string',
        'date_of_birth' => 'date',
        'marketing_opt_in' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
