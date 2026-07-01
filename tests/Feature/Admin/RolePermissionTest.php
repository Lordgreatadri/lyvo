<?php

namespace Tests\Feature\Admin;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(AccountType $type): User
    {
        static $seq = 100;
        $seq++;

        $user = User::factory()->create([
            'account_type' => $type->value,
            'status' => UserStatus::Active->value,
            'phone' => '024'.str_pad((string) $seq, 7, '0', STR_PAD_LEFT),
            'phone_verified_at' => now(),
        ]);

        $user->assignRole($type->defaultRole());

        return $user;
    }

    public function test_seeder_creates_a_role_for_every_account_type(): void
    {
        foreach (AccountType::cases() as $type) {
            $this->assertTrue(Role::where('name', $type->defaultRole())->exists());
        }

        $admin = Role::where('name', AccountType::Admin->defaultRole())->first();
        $this->assertSame(count(Permissions::all()), $admin->permissions->count());
    }

    public function test_admin_can_view_the_roles_screen(): void
    {
        $admin = $this->makeUser(AccountType::Admin);

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk();
    }

    public function test_non_admin_is_redirected_away_from_roles(): void
    {
        $operator = $this->makeUser(AccountType::Operator);

        $this->actingAs($operator)
            ->get(route('admin.roles.index'))
            ->assertRedirect(route('operator.dashboard'));
    }

    public function test_admin_can_sync_a_roles_permissions(): void
    {
        $admin = $this->makeUser(AccountType::Admin);
        $role = Role::where('name', AccountType::Customer->defaultRole())->first();

        $this->actingAs($admin)
            ->from(route('admin.roles.index'))
            ->put(route('admin.roles.update', $role), [
                'permissions' => [Permissions::DIRECTORY_VIEW],
            ])
            ->assertRedirect(route('admin.roles.index'));

        $role->refresh();

        $this->assertTrue($role->hasPermissionTo(Permissions::DIRECTORY_VIEW));
        $this->assertSame(1, $role->permissions->count());
    }
}
