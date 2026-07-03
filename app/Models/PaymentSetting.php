<?php

namespace App\Models;

use Dyrynda\Database\Support\BindsOnUuid;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * PaymentSetting
 * --------------
 * Single-row store for the admin-editable payment configuration (active gateway
 * and settlement currency). Access it through PaymentSetting::current(), which
 * memoises the row for the lifetime of the request so repeated reads never hit
 * the database more than once. Secrets are never stored here — they live in the
 * environment/config only.
 *
 * @property string $provider
 * @property string $currency
 */
class PaymentSetting extends Model
{
    use BindsOnUuid, GeneratesUuid;

    /**
     * Fixed value of the `singleton` column. A UNIQUE index on that column is what
     * actually enforces the one-row invariant at the database level.
     */
    private const SINGLETON = 1;

    protected $fillable = [
        'uuid',
        'singleton',
        'provider',
        'currency',
    ];

    protected $casts = [
        'singleton' => 'integer',
        'uuid' => 'string',
    ];

    private static ?PaymentSetting $cached = null;

    /**
     * The single settings row, created on first access from config defaults.
     *
     * The row is keyed on the unique `singleton` column, so the one-row invariant
     * is enforced by the database — not by convention. On Laravel 10 `firstOrCreate`
     * resolves a concurrent-creation race safely (it catches the unique-constraint
     * violation and re-queries the winning row), so no explicit lock is needed.
     * The result is memoised per-request to avoid repeat queries on hot paths.
     */
    public static function current(): self
    {
        return self::$cached ??= self::query()->firstOrCreate(
            ['singleton' => self::SINGLETON],
            [
                'provider' => config('payment.default', 'log'),
                'currency' => config('payment.currency', 'GHS'),
            ],
        );
    }

    /** Reset the per-request cache (used in tests and after updates). */
    public static function flushCache(): void
    {
        self::$cached = null;
    }
}
