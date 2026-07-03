<?php

namespace App\Providers;

use App\Enums\AccountType;
use App\Models\Product;
use App\Models\User;
use App\Policies\ProductPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
        Product::class => ProductPolicy::class,
    ];

    /**
     * Policy abilities that carry their own guard-rails (e.g. an admin may not
     * suspend / re-role / delete their own account). The super-admin gate defers
     * to these policies instead of blanket-allowing, so the guards still apply.
     *
     * @var array<int, string>
     */
    private const SELF_GUARDED_ABILITIES = ['suspend', 'assignRoles', 'delete'];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Super-admin: the admin role bypasses gate/permission checks so new
        // capabilities are available to admins the moment they are introduced —
        // except self-guarded policy abilities, which fall through to the policy
        // so an admin can never act destructively on their own account.
        Gate::before(function (User $user, string $ability) {
            if (! $user->hasRole(AccountType::Admin->defaultRole())) {
                return null;
            }

            if (in_array($ability, self::SELF_GUARDED_ABILITIES, true)) {
                return null;
            }

            return true;
        });
    }
}
