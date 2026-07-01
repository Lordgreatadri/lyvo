<?php

namespace Tests\Feature\Admin;

use App\Enums\AccountType;
use App\Enums\OperatorVerificationStatus;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Create a fully-verified user of the given type and assign its matching role.
     */
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

    public function test_admin_can_view_the_users_index(): void
    {
        $admin = $this->makeUser(AccountType::Admin);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_non_admin_is_redirected_away_from_user_management(): void
    {
        $customer = $this->makeUser(AccountType::Customer);

        $this->actingAs($customer)
            ->get(route('admin.users.index'))
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_admin_can_freeze_and_unfreeze_another_account(): void
    {
        $admin = $this->makeUser(AccountType::Admin);
        $target = $this->makeUser(AccountType::Customer);

        $this->actingAs($admin)
            ->from(route('admin.users.show', $target))
            ->patch(route('admin.users.freeze', $target))
            ->assertRedirect(route('admin.users.show', $target));

        $this->assertTrue($target->refresh()->isFrozen());

        $this->actingAs($admin)
            ->from(route('admin.users.show', $target))
            ->patch(route('admin.users.unfreeze', $target));

        $this->assertTrue($target->refresh()->isActive());
    }

    public function test_admin_cannot_freeze_their_own_account(): void
    {
        $admin = $this->makeUser(AccountType::Admin);

        $this->actingAs($admin)
            ->patch(route('admin.users.freeze', $admin))
            ->assertForbidden();

        $this->assertTrue($admin->refresh()->isActive());
    }

    public function test_admin_can_assign_roles_to_another_user(): void
    {
        $admin = $this->makeUser(AccountType::Admin);
        $target = $this->makeUser(AccountType::Customer);

        $this->actingAs($admin)
            ->from(route('admin.users.show', $target))
            ->put(route('admin.users.roles', $target), [
                'roles' => [AccountType::Operator->defaultRole()],
            ])
            ->assertRedirect(route('admin.users.show', $target));

        $this->assertTrue($target->refresh()->hasRole(AccountType::Operator->defaultRole()));
        $this->assertFalse($target->hasRole(AccountType::Customer->defaultRole()));
    }

    public function test_admin_cannot_assign_roles_to_their_own_account(): void
    {
        $admin = $this->makeUser(AccountType::Admin);

        $this->actingAs($admin)
            ->put(route('admin.users.roles', $admin), [
                'roles' => [AccountType::Customer->defaultRole()],
            ])
            ->assertForbidden();

        $this->assertTrue($admin->refresh()->hasRole(AccountType::Admin->defaultRole()));
    }

    public function test_admin_can_approve_a_pending_operator(): void
    {
        $admin = $this->makeUser(AccountType::Admin);
        $operator = $this->makeUser(AccountType::Operator);

        $profile = $operator->operatorProfile()->create([
            'business_name' => 'Acme Trading',
            'owner_full_name' => 'Ada Owner',
            'verification_status' => OperatorVerificationStatus::Pending->value,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.users.show', $operator))
            ->patch(route('admin.users.approve', $operator))
            ->assertRedirect(route('admin.users.show', $operator));

        $this->assertTrue($profile->refresh()->isApproved());
    }
}
