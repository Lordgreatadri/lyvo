<?php

namespace App\Models;

use App\Enums\PayoutChannel;
use App\Enums\PayoutStatus;
use Dyrynda\Database\Support\BindsOnUuid;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Payout
 * ------
 * A single Moolre disbursement (transfer) sending money from the platform's
 * Moolre wallet to a recipient's mobile-money wallet or bank account — typically
 * an operator being paid out once escrow funds are released. One row is written
 * when a payout is initiated and updated in place as the transfer response,
 * status polls and settlement webhook arrive. The polymorphic `payable` links a
 * payout to whatever it settles (e.g. an escrow order) without coupling domains.
 *
 * @property string $ref
 * @property PayoutStatus $status
 * @property PayoutChannel $channel
 */
class Payout extends Model
{
    use BindsOnUuid, GeneratesUuid;

    protected $fillable = [
        'ref',
        'uuid',
        'provider',
        'provider_transaction_id',
        'third_party_ref',
        'channel',
        'currency',
        'amount',
        'fee',
        'recipient',
        'recipient_name',
        'account_number',
        'sublist_id',
        'status',
        'context',
        'reference',
        'user_id',
        'initiated_by',
        'payable_type',
        'payable_id',
        'failure_reason',
        'meta',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'status' => PayoutStatus::class,
        'channel' => PayoutChannel::class,
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'meta' => 'array',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'uuid' => 'string',
    ];

    /** The operator (user) being paid. */
    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The admin who initiated the payout. */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /** The thing this payout settles (escrow order, etc.). */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @param  Builder<Payout>  $query */
    public function scopeWithStatus(Builder $query, PayoutStatus $status): void
    {
        $query->where('status', $status->value);
    }

    /** @param  Builder<Payout>  $query */
    public function scopeSuccessful(Builder $query): void
    {
        $query->where('status', PayoutStatus::Successful->value);
    }
}
