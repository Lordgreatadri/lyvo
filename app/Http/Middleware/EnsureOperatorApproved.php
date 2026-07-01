<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureOperatorApproved
 * ----------------------
 * Locks the operator dashboard until an admin approves the business. Unapproved
 * operators are redirected to a status page that shows their progress through
 * the verification workflow.
 */
class EnsureOperatorApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && $user->isOperator(), 403);

        $profile = $user->operatorProfile;

        if (! $profile || ! $profile->isApproved()) {
            return redirect()->route('operator.pending');
        }

        return $next($request);
    }
}
