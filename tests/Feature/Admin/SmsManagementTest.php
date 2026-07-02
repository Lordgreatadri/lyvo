<?php

namespace Tests\Feature\Admin;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin SMS console: access control, viewing, configuration and test sends.
 * The default (log) driver keeps every assertion network-free.
 */
class SmsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(AccountType $type): User
    {
        static $seq = 0;
        $seq++;

        $user = User::factory()->create([
            'account_type' => $type->value,
            'status' => UserStatus::Active->value,
            'phone' => '020'.str_pad((string) $seq, 7, '0', STR_PAD_LEFT),
            'phone_verified_at' => now(),
        ]);

        $user->assignRole($type->defaultRole());

        return $user;
    }

    public function test_admin_can_view_the_sms_console(): void
    {
        $admin = $this->makeUser(AccountType::Admin);

        $this->actingAs($admin)
            ->get(route('admin.sms.index'))
            ->assertOk()
            ->assertSee('SMS Gateway');
    }

    public function test_non_admin_is_blocked_from_the_sms_console(): void
    {
        $customer = $this->makeUser(AccountType::Customer);

        $this->actingAs($customer)
            ->get(route('admin.sms.index'))
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_admin_can_update_sms_settings(): void
    {
        $admin = $this->makeUser(AccountType::Admin);

        $this->actingAs($admin)
            ->put(route('admin.sms.settings'), [
                'provider' => 'log',
                'sender_id' => 'LYVO',
                'low_credit_threshold' => 500,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sms_settings', [
            'provider' => 'log',
            'sender_id' => 'LYVO',
            'low_credit_threshold' => 500,
        ]);
    }

    public function test_admin_can_send_a_test_message(): void
    {
        $admin = $this->makeUser(AccountType::Admin);

        $this->actingAs($admin)
            ->post(route('admin.sms.test'), [
                'recipient' => '0201234567',
                'message' => 'Test from LYVO',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sms_messages', [
            'recipient' => '+233201234567',
            'context' => 'admin-test',
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_can_refresh_the_balance(): void
    {
        $admin = $this->makeUser(AccountType::Admin);

        $this->actingAs($admin)
            ->post(route('admin.sms.balance'))
            ->assertRedirect();

        $this->assertDatabaseHas('sms_settings', ['provider' => 'log']);
        $this->assertNotNull(\App\Models\SmsSetting::current()->balance_checked_at);
    }
}
