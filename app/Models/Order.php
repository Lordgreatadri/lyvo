<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Models\EscrowEvent;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use Dyrynda\Database\Support\BindsOnUuid;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Order
 * -----
 * An escrow-protected purchase by a customer from a single operator. The money
 * side lives on the linked PaymentTransaction; this model owns the fulfilment /
 * escrow lifecycle (see OrderStatus and Src\Domain\Commerce\EscrowService).
 *
 * @property OrderStatus $status
 */
class Order extends Model
{
    use BindsOnUuid, GeneratesUuid, HasFactory;

    protected $fillable = [
        'uuid',
        'order_number',
        'customer_id',
        'operator_profile_id',
        'payment_transaction_id',
        'status',
        'subtotal',
        'total',
        'currency',
        'delivery_recipient',
        'delivery_phone',
        'delivery_address',
        'delivery_note',
        'placed_at',
        'funds_held_at',
        'processing_at',
        'delivered_at',
        'released_at',
        'disputed_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'status' => OrderStatus::class,
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'placed_at' => 'datetime',
        'funds_held_at' => 'datetime',
        'processing_at' => 'datetime',
        'delivered_at' => 'datetime',
        'released_at' => 'datetime',
        'disputed_at' => 'datetime',
    ];

    /* ----------------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------------*/

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(OperatorProfile::class, 'operator_profile_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(EscrowEvent::class)->latest();
    }

    /* ----------------------------------------------------------------------
     | Scopes
     * --------------------------------------------------------------------*/

    /** @param  Builder<Order>  $query */
    public function scopeForOperator(Builder $query, int $operatorProfileId): void
    {
        $query->where('operator_profile_id', $operatorProfileId);
    }

    /** @param  Builder<Order>  $query */
    public function scopeForCustomer(Builder $query, int $customerId): void
    {
        $query->where('customer_id', $customerId);
    }

    /** Orders whose funds are currently held in escrow. @param Builder<Order> $query */
    public function scopeEscrowHeld(Builder $query): void
    {
        $query->whereIn('status', [
            OrderStatus::FundsHeld->value,
            OrderStatus::Processing->value,
            OrderStatus::Delivered->value,
            OrderStatus::Disputed->value,
        ]);
    }
}
