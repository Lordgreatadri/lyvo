<?php

namespace App\Models;

use Dyrynda\Database\Support\BindsOnUuid;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OperatorVerificationEvent
 * -------------------------
 * Append-only audit record of a single verification status transition.
 */
class OperatorVerificationEvent extends Model
{
    use BindsOnUuid, GeneratesUuid;

    protected $fillable = [
        'operator_profile_id',
        'actor_id',
        'from_status',
        'to_status',
        'notes',
    ];

    protected $casts = [
        'uuid' => 'string',
    ];

    public function operatorProfile(): BelongsTo
    {
        return $this->belongsTo(OperatorProfile::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
