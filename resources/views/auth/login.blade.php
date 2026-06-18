<x-layouts.auth title="Welcome back" subtitle="Log in to your LYVO account to manage transactions and trusted operators.">

    <x-auth-session-status class="mb-4" :status="session('status')" />

    {{-- Account type tabs (visual context for the demo) --}}
    <div x-data="{ tab: 'customer' }" class="mb-6">
        <div class="grid grid-cols-2 gap-2 rounded-2xl bg-surface-muted p-1.5">
            <button type="button" @click="tab = 'customer'"
                    :class="tab === 'customer' ? 'bg-white text-ink shadow-soft' : 'text-ink-muted'"
                    class="flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold transition">
                <x-icon name="user" class="h-4 w-4" /> Customer
            </button>
            <button type="button" @click="tab = 'operator'"
                    :class="tab === 'operator' ? 'bg-white text-ink shadow-soft' : 'text-ink-muted'"
                    class="flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold transition">
                <x-icon name="badge" class="h-4 w-4" /> Operator
            </button>
        </div>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="form-label">Email or phone number</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="form-input" placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="form-label">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs font-medium text-primary-600 hover:underline">Forgot password?</a>
                @endif
            </div>
            <x-password-input name="password" :label="false" autocomplete="current-password" required>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </x-password-input>
        </div>

        <label for="remember_me" class="flex items-center gap-2 text-sm text-ink-soft">
            <input id="remember_me" type="checkbox" name="remember" class="rounded border-slate-300 text-primary-600 focus:ring-primary-400">
            Keep me signed in
        </label>

        <button type="submit" class="btn-primary w-full">Log in</button>
    </form>

    <div class="my-6 flex items-center gap-4">
        <div class="h-px flex-1 bg-slate-100"></div>
        <span class="text-xs font-medium text-ink-muted">or</span>
        <div class="h-px flex-1 bg-slate-100"></div>
    </div>

    <a href="{{ route('guest.enter') }}" class="btn-outline w-full">
        <x-icon name="eye" class="h-4 w-4" /> Continue as Guest
    </a>
    <p class="mt-2 text-center text-xs text-ink-muted">Browse operators &amp; reviews. Sign up to unlock secure transactions.</p>

    <p class="mt-8 text-center text-sm text-ink-soft">
        New to LYVO?
        <a href="{{ route('register') }}" class="font-semibold text-primary-600 hover:underline">Create an account</a>
    </p>

</x-layouts.auth>
