<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Concerns\RedirectsUsers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    use RedirectsUsers;

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        // Suspended / banned accounts may authenticate credentials but cannot proceed.
        if ($user->status !== UserStatus::Active) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => 'Your account is '.$user->status->label().'. Please contact support.',
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $request->session()->regenerate();

        return $this->redirectAfterAuth($user);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
