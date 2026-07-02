<?php

namespace App\Models;

use App\Enums\SmsStatus;
// use App\Models\User;
use Dyrynda\Database\Support\BindsOnUuid;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SmsMessage
 * ----------
 * A single outbound SMS and its delivery lifecycle. Rows are append-only history
 * updated in place as receipts arrive.
 *
 * @property string $ref
 * @property SmsStatus $status
 */
class SmsMessage extends Model
{
    use BindsOnUuid, GeneratesUuid;
    
    protected $fillable = [
        'ref',
        'uuid',
        'provider',
        'sender_id',
        'recipient',
        'message',
        'context',
        'user_id',
        'status',
        'encoding',
        'segments',
        'provider_message_id',
        'failure_reason',
        'meta',
        'sent_at',
        'delivered_at',
        'failed_at',
    ];

    protected $casts = [
        'status' => SmsStatus::class,
        'segments' => 'integer',
        'meta' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'uuid' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param  Builder<SmsMessage>  $query */
    public function scopeWithStatus(Builder $query, SmsStatus $status): void
    {
        $query->where('status', $status->value);
    }

    /** @param  Builder<SmsMessage>  $query */
    public function scopeFailed(Builder $query): void
    {
        $query->where('status', SmsStatus::Failed->value);
    }
}
