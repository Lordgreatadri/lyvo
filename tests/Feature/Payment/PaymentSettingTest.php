<?php

namespace Tests\Feature\Payment;

use App\Models\PaymentSetting;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the single-row invariant on payment_settings: current() always resolves
 * one row, and the unique `singleton` column makes a second row impossible.
 */
class PaymentSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PaymentSetting::flushCache();
    }

    public function test_current_creates_and_reuses_a_single_row(): void
    {
        $first = PaymentSetting::current();

        PaymentSetting::flushCache();
        $second = PaymentSetting::current();

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, PaymentSetting::query()->count());
        $this->assertSame(config('payment.default', 'log'), $first->provider);
    }

    public function test_a_second_singleton_row_is_rejected_by_the_database(): void
    {
        PaymentSetting::current();

        $this->expectException(QueryException::class);

        // Bypass the guarded accessor to attempt a duplicate — the UNIQUE index
        // on `singleton` must reject it.
        PaymentSetting::query()->create([
            'singleton' => 1,
            'provider' => 'moolre',
            'currency' => 'GHS',
        ]);
    }
}
