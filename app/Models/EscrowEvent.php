<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * EscrowEvent
 * -----------
 * One entry in an order's immutable escrow audit trail.
 */
class EscrowEvent extends Model
{
    protected $fillable = [
        'order_id',
        'actor_id',
        'from_status',
        'to_status',
        'note',
    ];

    protected $casts = [
        'from_status' => OrderStatus::class,
        'to_status' => OrderStatus::class,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
