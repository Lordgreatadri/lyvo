<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;

/**
 * UserPolicy
 * ----------
 * Authorizes admin user-management actions. The super-admin gate in
 * AuthServiceProvider short-circuits every check for the admin role, so these
 * methods describe what a *non-admin* holder of the relevant permission may do
 * and keep sensible guard-rails (e.g. no self-suspension).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::USERS_VIEW);
    }

    public function view(User $user, User $model): bool
    {
        return $user->can(Permissions::USERS_VIEW);
    }

    public function approve(User $user, User $model): bool
    {
        return $user->can(Permissions::USERS_APPROVE);
    }

    /**
     * Freeze / unfreeze. An admin may never freeze their own account.
     */
    public function suspend(User $user, User $model): bool
    {
        return $user->can(Permissions::USERS_SUSPEND) && $user->isNot($model);
    }

    public function assignRoles(User $user, User $model): bool
    {
        return $user->can(Permissions::ROLES_ASSIGN) && $user->isNot($model);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can(Permissions::USERS_DELETE) && $user->isNot($model);
    }
}
