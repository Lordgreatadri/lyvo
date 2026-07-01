<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * RedirectsUsers
 * --------------
 * Central post-authentication routing. Until both email and phone are verified,
 * users are funnelled to the verification screen; afterwards they land on the
 * dashboard for their account type (operator approval is enforced separately by
 * the EnsureOperatorApproved middleware).
 */
trait RedirectsUsers
{
    protected function redirectAfterAuth(User $user): RedirectResponse
    {
        if (! $user->isFullyVerified()) {
            return redirect()->route('verification.notice');
        }

        // Operators awaiting (or declined by) admin review land on their status
        // page instead of the locked dashboard.
        if ($user->isOperator() && ! $user->operatorProfile?->isApproved()) {
            return redirect()->route('operator.pending');
        }

        return redirect()->route($user->homeRoute());
    }
}
