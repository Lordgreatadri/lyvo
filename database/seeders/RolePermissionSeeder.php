<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * RolePermissionSeeder
 * --------------------
 * Seeds the platform's roles and permissions from the central catalogue
 * (App\Support\Permissions). Roles map 1:1 to account types (admin, operator,
 * customer); guests are unauthenticated and therefore hold no role.
 *
 * The seeder is idempotent — re-run it any time new permissions are added to the
 * catalogue and it creates the missing ones and re-syncs each role's defaults.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Ensure every catalogued permission exists.
        foreach (Permissions::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // 2. Create each account-type role and sync its default permission set.
        foreach (AccountType::cases() as $accountType) {
            $role = Role::findOrCreate($accountType->defaultRole(), 'web');
            $role->syncPermissions(Permissions::forRole($accountType));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
