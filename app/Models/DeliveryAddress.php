<?php

namespace App\Models;

use Dyrynda\Database\Support\BindsOnUuid;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * DeliveryAddress
 * ---------------
 * A customer shipping address. The 3-address limit and single-default rule are
 * enforced by the application (DeliveryAddressController / DeliveryAddressService).
 */
class DeliveryAddress extends Model
{
    use BindsOnUuid, GeneratesUuid, SoftDeletes;

    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'region',
        'city',
        'area',
        'address_line',
        'landmark',
        'is_default',
    ];

    protected $casts = [
        'uuid' => 'string',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
