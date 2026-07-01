<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;
use Spatie\Permission\Models\Role;

/**
 * RolePolicy
 * ----------
 * Authorizes viewing and editing of roles and their permissions.
 */
class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::ROLES_VIEW);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can(Permissions::ROLES_MANAGE);
    }
}
