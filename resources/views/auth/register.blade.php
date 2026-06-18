<x-layouts.auth title="Create your account" subtitle="Join LYVO to transact safely with verified operators across Ghana.">

    {{-- Path chooser: Customer vs Operator --}}
    <div class="mb-6 grid gap-3 sm:grid-cols-2">
        <div class="card border-2 border-primary-500 p-4">
            <div class="flex items-center gap-2 text-primary-700">
                <x-icon name="user" class="h-5 w-5" />
                <span class="text-sm font-bold">Customer</span>
            </div>
            <p class="mt-1.5 text-xs text-ink-muted">Buy safely with escrow protection.</p>
        </div>
        <a href="{{ route('register.operator') }}" class="card card-hover border border-sky-200 bg-sky-50 p-4">
            <div class="flex items-center gap-2 text-sky-800">
                <x-icon name="badge" class="h-5 w-5" />
                <span class="text-sm font-bold">Operator</span>
            </div>
            <p class="mt-1.5 text-xs text-sky-600/80">Sell as a verified business. <span class="font-medium text-sky-700">Apply &rarr;</span></p>
        </a>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <label for="name" class="form-label">Full name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name"
                   class="form-input" placeholder="Ama Owusu" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <label for="phone" class="form-label">Phone number</label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel"
                   class="form-input" placeholder="+233 24 000 0000" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div>
            <label for="email" class="form-label">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"
                   class="form-input" placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-password-input name="password" label="Password" required>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </x-password-input>
            <x-password-input name="password_confirmation" label="Confirm password" required>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </x-password-input>
        </div>

        <button type="submit" class="btn-primary w-full">Create account</button>
    </form>

    <p class="mt-8 text-center text-sm text-ink-soft">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-primary-600 hover:underline">Log in</a>
    </p>

</x-layouts.auth>
