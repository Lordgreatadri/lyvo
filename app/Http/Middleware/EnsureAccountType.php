<?php

namespace App\Http\Middleware;

use App\Enums\AccountType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureAccountType
 * -----------------
 * Restricts a route to one or more account types. Usage: account:operator or
 * account:admin,customer. A signed-in user of the wrong type is redirected to
 * their own dashboard rather than shown a raw 403.
 */
class EnsureAccountType
{
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $user = $request->user();

        abort_unless($user, 403);

        $allowed = array_map(fn (string $t) => AccountType::from($t), $types);

        if (! in_array($user->account_type, $allowed, true)) {
            return redirect()->route($user->homeRoute());
        }

        return $next($request);
    }
}
