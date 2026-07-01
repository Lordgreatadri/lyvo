<?php

namespace App\Models;

use App\Enums\PaymentMethodType;
use Dyrynda\Database\Support\BindsOnUuid;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PaymentMethod
 * -------------
 * A saved, non-sensitive payment instrument for a customer.
 */
class PaymentMethod extends Model
{
    use BindsOnUuid, GeneratesUuid, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'provider',
        'account_name',
        'account_reference',
        'is_default',
    ];

    protected $casts = [
        'uuid' => 'string',
        'type' => PaymentMethodType::class,
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
