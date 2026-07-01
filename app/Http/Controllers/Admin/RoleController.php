<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRolePermissionsRequest;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Admin\RoleController
 * --------------------
 * Manage roles and reassign their permissions. Permissions are presented grouped
 * by domain (from the central Permissions catalogue) so the matrix stays legible
 * as the platform grows.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        return view('admin.roles.index', [
            'roles' => Role::with('permissions')->withCount('users')->orderBy('name')->get(),
            'groups' => Permissions::groups(),
        ]);
    }

    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $role->syncPermissions($request->validated('permissions') ?? []);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('status', ucfirst($role->name).' permissions updated.');
    }
}
