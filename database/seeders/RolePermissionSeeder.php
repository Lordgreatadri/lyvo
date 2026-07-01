<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * RolePermissionSeeder
 * --------------------
 * Seeds the three account-type roles (admin, customer, operator) and a starter
 * set of permissions. Fine-grained authorization is fleshed out in the next
 * phase; this gives every account a role from day one so the gate is ready.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Operator verification
            'operators.verify',
            'operators.view',
            // User management
            'users.manage',
            // Escrow / disputes (future phases — declared early)
            'escrow.manage',
            'disputes.resolve',
            // Customer self-service
            'addresses.manage',
            'payment-methods.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $admin = Role::findOrCreate(AccountType::Admin->defaultRole(), 'web');
        $customer = Role::findOrCreate(AccountType::Customer->defaultRole(), 'web');
        $operator = Role::findOrCreate(AccountType::Operator->defaultRole(), 'web');

        // Admin can do everything currently defined.
        $admin->syncPermissions(Permission::all());

        $customer->syncPermissions(['addresses.manage', 'payment-methods.manage']);

        $operator->syncPermissions(['operators.view']);
    }
}
