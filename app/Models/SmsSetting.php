<?php

namespace App\Models;

use Dyrynda\Database\Support\BindsOnUuid;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * SmsSetting
 * ----------
 * Single-row store for the admin-editable SMS configuration. Access it through
 * SmsSetting::current(), which memoises the row for the lifetime of the request
 * so repeated reads never hit the database more than once.
 *
 * @property string $provider
 * @property string|null $sender_id
 * @property int $low_credit_threshold
 * @property float|null $cached_balance
 */
class SmsSetting extends Model
{
    use BindsOnUuid, GeneratesUuid;

    protected $fillable = [
        'uuid',
        'provider',
        'sender_id',
        'low_credit_threshold',
        'cached_balance',
        'cached_balance_snapshot',
        'balance_checked_at',
        'low_credit_alerted_at',
    ];

    protected $casts = [
        'low_credit_threshold' => 'integer',
        'cached_balance' => 'decimal:2',
        'cached_balance_snapshot' => 'array',
        'balance_checked_at' => 'datetime',
        'low_credit_alerted_at' => 'datetime',
        'uuid' => 'string',
    ];

    private static ?SmsSetting $cached = null;

    /**
     * The single settings row, created on first access from config defaults.
     * Memoised per-request to avoid repeat queries on hot paths (every send).
     */
    public static function current(): self
    {
        return self::$cached ??= self::query()->firstOrCreate([], [
            'provider' => config('sms.default', 'log'),
            'sender_id' => config('sms.sender_id', 'LYVO'),
            'low_credit_threshold' => (int) config('sms.low_credit_threshold', 100),
        ]);
    }

    /** Reset the per-request cache (used in tests and after updates). */
    public static function flushCache(): void
    {
        self::$cached = null;
    }

    public function isBalanceStale(?int $minutes = null): bool
    {
        if ($this->balance_checked_at === null) {
            return true;
        }

        $minutes ??= (int) config('sms.balance_cache_minutes', 15);

        return $this->balance_checked_at->lt(Carbon::now()->subMinutes($minutes));
    }

    public function isBelowThreshold(): bool
    {
        return $this->cached_balance !== null
            && (float) $this->cached_balance < (float) $this->low_credit_threshold;
    }

    public function effectiveSenderId(): string
    {
        return $this->sender_id ?: (string) config('sms.sender_id', 'LYVO');
    }
}
