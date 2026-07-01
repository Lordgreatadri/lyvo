<?php

namespace App\Models;

use Dyrynda\Database\Support\BindsOnUuid;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GuestCustomer
 * -------------
 * A call-in / walk-in customer captured by a representative so an order can be
 * created without prior registration. Links to a User once they register.
 */
class GuestCustomer extends Model
{
    use BindsOnUuid, GeneratesUuid;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'notes',
        'created_by',
        'user_id',
    ];

    protected $casts = [
        'uuid' => 'string',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
