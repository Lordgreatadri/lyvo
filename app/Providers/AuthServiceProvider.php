<?php

namespace App\Providers;

use App\Enums\AccountType;
use App\Models\User;
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
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Super-admin: the admin role bypasses every gate/permission check so new
        // capabilities are available to admins the moment they are introduced.
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole(AccountType::Admin->defaultRole()) ? true : null;
        });
    }
}
