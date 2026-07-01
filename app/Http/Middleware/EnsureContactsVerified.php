<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureContactsVerified
 * ----------------------
 * Requires BOTH email and phone to be verified (LYVO verifies every contact via
 * OTP). Unverified users are sent to the verification screen.
 */
class EnsureContactsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user, 403);

        if (! $user->isFullyVerified()) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
