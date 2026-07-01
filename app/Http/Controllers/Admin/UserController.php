<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountType;
use App\Enums\OperatorVerificationStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRolesRequest;
use App\Models\OperatorProfile;
use App\Models\User;
use App\Services\OperatorReviewService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Admin\UserController
 * --------------------
 * User-management workspace: browse/search every account, review and approve
 * pending operators, freeze/unfreeze accounts and assign roles. Every action is
 * gated by the UserPolicy (admins pass via the super-admin gate). Users resolve
 * by uuid.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $filters = [
            'type' => $request->string('type')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'search' => $request->string('search')->toString() ?: null,
            'pending' => $request->boolean('pending'),
        ];

        return view('admin.users.index', [
            'users' => $this->query($filters),
            'filters' => $filters,
            'accountTypes' => AccountType::cases(),
            'statuses' => UserStatus::cases(),
            'counts' => [
                'total' => User::count(),
                'operators' => User::where('account_type', AccountType::Operator->value)->count(),
                'customers' => User::where('account_type', AccountType::Customer->value)->count(),
                'frozen' => User::where('status', UserStatus::Suspended->value)->count(),
                'pending' => $this->pendingOperatorsQuery()->count(),
            ],
        ]);
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load([
            'roles.permissions',
            'operatorProfile.category',
            'operatorProfile.verificationEvents.actor',
            'customerProfile',
        ]);

        return view('admin.users.show', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    /**
     * Approve a pending operator directly from user management.
     */
    public function approve(User $user, OperatorReviewService $review): RedirectResponse
    {
        $this->authorize('approve', $user);

        abort_unless($user->isOperator() && $user->operatorProfile, 404);

        $review->approve($user->operatorProfile, request()->user());

        return back()->with('status', $user->name.' has been approved.');
    }

    public function freeze(User $user): RedirectResponse
    {
        $this->authorize('suspend', $user);

        $user->freeze();

        return back()->with('status', $user->name.'’s account has been frozen.');
    }

    public function unfreeze(User $user): RedirectResponse
    {
        $this->authorize('suspend', $user);

        $user->unfreeze();

        return back()->with('status', $user->name.'’s account has been reactivated.');
    }

    public function updateRoles(UpdateUserRolesRequest $request, User $user): RedirectResponse
    {
        $this->authorize('assignRoles', $user);

        $user->syncRoles($request->validated('roles') ?? []);

        return back()->with('status', 'Roles updated for '.$user->name.'.');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->when($filters['type'], fn (Builder $q, $type) => $q->where('account_type', $type))
            ->when($filters['status'], fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['pending'], fn (Builder $q) => $q->whereHas('operatorProfile', fn (Builder $p) => $p->whereIn('verification_status', [
                OperatorVerificationStatus::Pending->value,
                OperatorVerificationStatus::InReview->value,
            ])))
            ->when($filters['search'], function (Builder $q, $search) {
                $q->where(function (Builder $q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    private function pendingOperatorsQuery(): Builder
    {
        return OperatorProfile::query()->whereIn('verification_status', [
            OperatorVerificationStatus::Pending->value,
            OperatorVerificationStatus::InReview->value,
        ]);
    }
}
