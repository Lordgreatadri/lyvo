<?php

namespace App\Models;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use Dyrynda\Database\Support\BindsOnUuid;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * PaymentTransaction
 * ------------------
 * A single Moolre collection and its settlement lifecycle. One row is written
 * when a payment is initiated and updated in place as OTP/approval steps and
 * webhook/status receipts arrive. The polymorphic `payable` links a transaction
 * to whatever it funds (e.g. an escrow order) without coupling the two domains.
 *
 * @property string $ref
 * @property PaymentStatus $status
 * @property PaymentChannel $channel
 */
class PaymentTransaction extends Model
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
        'value',
        'payer',
        'account_number',
        'status',
        'context',
        'reference',
        'user_id',
        'payable_type',
        'payable_id',
        'otp_required',
        'session_id',
        'failure_reason',
        'meta',
        'authorized_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'status' => PaymentStatus::class,
        'channel' => PaymentChannel::class,
        'amount' => 'decimal:2',
        'value' => 'decimal:2',
        'otp_required' => 'boolean',
        'meta' => 'array',
        'authorized_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'uuid' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The thing this payment funds (escrow order, wallet top-up, …). */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @param  Builder<PaymentTransaction>  $query */
    public function scopeWithStatus(Builder $query, PaymentStatus $status): void
    {
        $query->where('status', $status->value);
    }

    /** @param  Builder<PaymentTransaction>  $query */
    public function scopeSuccessful(Builder $query): void
    {
        $query->where('status', PaymentStatus::Successful->value);
    }

    /** @param  Builder<PaymentTransaction>  $query */
    public function scopeFailed(Builder $query): void
    {
        $query->where('status', PaymentStatus::Failed->value);
    }
}
