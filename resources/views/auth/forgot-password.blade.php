<x-layouts.auth title="Reset your password" subtitle="Enter your email and we'll send you a secure link to choose a new password.">

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6 flex items-start gap-3 rounded-2xl bg-sky-50 p-4 text-sm text-sky-700">
        <x-icon name="lock" class="mt-0.5 h-4 w-4 shrink-0" />
        <p>Forgot your password? No problem. Tell us your email address and we'll email you a password reset link to choose a new one.</p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="form-label">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="form-input" placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button type="submit" class="btn-primary w-full">
            <x-icon name="message" class="h-4 w-4" />
            Email password reset link
        </button>
    </form>

    <p class="mt-8 text-center text-sm text-ink-soft">
        Remembered your password?
        <a href="{{ route('login') }}" class="font-semibold text-primary-600 hover:underline">Back to log in</a>
    </p>

</x-layouts.auth>
