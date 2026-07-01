<x-layouts.auth title="Verify your account" subtitle="We sent a one-time code to your email and phone. Enter both to secure your account.">

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @php
        $emailVerified = ! is_null($user->email_verified_at);
        $phoneVerified = ! is_null($user->phone_verified_at);
    @endphp

    <div class="rounded-2xl bg-sky-50 p-4 text-xs text-sky-800">
        <div class="flex items-start gap-2">
            <x-icon name="shield" class="mt-0.5 h-4 w-4 shrink-0" />
            <p>During development your verification codes are written to <span class="font-semibold">storage/logs/laravel.log</span> — no SMS or email gateway is required to test.</p>
        </div>
    </div>

    {{-- ============ EMAIL ============ --}}
    <div class="card mt-6 p-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <x-icon name="mail" class="h-5 w-5 text-primary-600" />
                <span class="text-sm font-semibold text-ink">Email · {{ $user->email }}</span>
            </div>
            @if ($emailVerified)
                <span class="badge-verified"><x-icon name="check" class="h-3.5 w-3.5" /> Verified</span>
            @else
                <span class="badge-info">Pending</span>
            @endif
        </div>

        @unless ($emailVerified)
            <form method="POST" action="{{ route('otp.verify', 'email') }}" class="mt-4 flex items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label for="email_code" class="form-label">Enter email code</label>
                    <input id="email_code" name="code" inputmode="numeric" autocomplete="one-time-code"
                           class="form-input tracking-[0.3em]" placeholder="••••••" />
                    <x-input-error :messages="$errors->getBag('email')->get('code')" class="mt-2" />
                </div>
                <button type="submit" class="btn-primary">Verify</button>
            </form>
            <form method="POST" action="{{ route('otp.send', 'email') }}" class="mt-2">
                @csrf
                <button type="submit" class="text-xs font-medium text-primary-600 hover:underline">Resend email code</button>
            </form>
        @endunless
    </div>

    {{-- ============ PHONE ============ --}}
    <div class="card mt-4 p-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <x-icon name="phone" class="h-5 w-5 text-primary-600" />
                <span class="text-sm font-semibold text-ink">Phone · {{ $user->phone }}</span>
            </div>
            @if ($phoneVerified)
                <span class="badge-verified"><x-icon name="check" class="h-3.5 w-3.5" /> Verified</span>
            @else
                <span class="badge-info">Pending</span>
            @endif
        </div>

        @unless ($phoneVerified)
            <form method="POST" action="{{ route('otp.verify', 'phone') }}" class="mt-4 flex items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label for="phone_code" class="form-label">Enter phone code</label>
                    <input id="phone_code" name="code" inputmode="numeric" autocomplete="one-time-code"
                           class="form-input tracking-[0.3em]" placeholder="••••••" />
                    <x-input-error :messages="$errors->getBag('phone')->get('code')" class="mt-2" />
                </div>
                <button type="submit" class="btn-primary">Verify</button>
            </form>
            <form method="POST" action="{{ route('otp.send', 'phone') }}" class="mt-2">
                @csrf
                <button type="submit" class="text-xs font-medium text-primary-600 hover:underline">Resend phone code</button>
            </form>
        @endunless
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mt-8 text-center">
        @csrf
        <button type="submit" class="text-sm text-ink-soft hover:text-rose-500">Log out</button>
    </form>

</x-layouts.auth>
