<?php

namespace Tests\Feature\Admin;

use App\Enums\AccountType;
use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\UserStatus;
use App\Models\PaymentTransaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the admin dashboard renders the Moolre payments performance overview
 * and pulls real aggregate figures from the transaction ledger.
 */
class PaymentOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create([
            'account_type' => AccountType::Admin->value,
            'status' => UserStatus::Active->value,
            'phone_verified_at' => now(),
        ]);

        $user->assignRole(AccountType::Admin->defaultRole());

        return $user;
    }

    private function transaction(PaymentStatus $status, float $amount, PaymentChannel $channel = PaymentChannel::Mtn): PaymentTransaction
    {
        return PaymentTransaction::create([
            'ref' => 'ref-' . uniqid('', true),
            'provider' => 'log',
            'channel' => $channel,
            'currency' => 'GHS',
            'amount' => $amount,
            'payer' => '+233201234567',
            'status' => $status,
            'context' => 'order',
        ]);
    }

    public function test_admin_dashboard_shows_the_payments_overview(): void
    {
        $this->transaction(PaymentStatus::Successful, 100.0, PaymentChannel::Mtn);
        $this->transaction(PaymentStatus::Successful, 50.0, PaymentChannel::Telecel);
        $this->transaction(PaymentStatus::Failed, 20.0);
        $this->transaction(PaymentStatus::AwaitingApproval, 30.0);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Payments')
            ->assertSee('Collected (all time)')
            ->assertSee('Success rate')
            ->assertSee('MTN Mobile Money');
    }

    public function test_overview_aggregates_are_accurate(): void
    {
        $this->transaction(PaymentStatus::Successful, 100.0);
        $this->transaction(PaymentStatus::Successful, 50.0);
        $this->transaction(PaymentStatus::Failed, 20.0);
        $this->transaction(PaymentStatus::AwaitingApproval, 30.0);

        $overview = app(\Src\Domain\Payment\Reporting\PaymentOverview::class)->forAdmin();

        $this->assertSame(4, $overview['totals']['total']);
        $this->assertSame(2, $overview['totals']['successful']);
        $this->assertSame(1, $overview['totals']['failed']);
        $this->assertSame(1, $overview['totals']['open']);
        $this->assertSame(150.0, $overview['totals']['collected']);
        // 2 successful of 3 settled (2 successful + 1 failed) = 67%.
        $this->assertSame(67.0, $overview['totals']['success_rate']);
    }
}
