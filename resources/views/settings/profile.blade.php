<x-layouts.dashboard :role="$role" title="Profile & Settings" heading="Profile & Settings" subheading="Manage your personal information, contact details and password.">

    @php
        $flash = session('status');
    @endphp

    @if ($flash === 'profile-updated')
        <div class="mb-4 rounded-2xl bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">Your profile has been updated. If you changed your email or phone, check for a new verification code.</div>
    @elseif ($flash === 'password-updated')
        <div class="mb-4 rounded-2xl bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">Your password has been updated.</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Profile information --}}
        <div class="card p-6 lg:col-span-2">
            <h2 class="font-semibold text-ink">Personal information</h2>
            <p class="mt-1 text-sm text-ink-muted">Changing your email or phone number will require you to verify it again.</p>

            <form method="POST" action="{{ route('profile.update') }}" class="mt-5 space-y-5">
                @csrf @method('PATCH')

                <div>
                    <label for="name" class="form-label">Full name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" class="form-input" required autofocus autocomplete="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <label for="email" class="form-label">Email address</label>
                    <div class="flex items-center gap-2">
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="form-input" required autocomplete="email" />
                        @if ($user->email_verified_at)
                            <span class="badge badge-verified shrink-0">Verified</span>
                        @else
                            <span class="badge bg-amber-50 text-amber-700 shrink-0">Unverified</span>
                        @endif
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <div>
                    <label for="phone" class="form-label">Phone number</label>
                    <div class="flex items-center gap-2">
                        <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" class="form-input" required autocomplete="tel" />
                        @if ($user->phone_verified_at)
                            <span class="badge badge-verified shrink-0">Verified</span>
                        @else
                            <span class="badge bg-amber-50 text-amber-700 shrink-0">Unverified</span>
                        @endif
                    </div>
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>

                <button type="submit" class="btn-primary">Save changes</button>
            </form>
        </div>

        {{-- Account summary --}}
        <div class="space-y-4">
            <div class="card p-5">
                <p class="text-xs font-medium text-ink-muted">Account type</p>
                <p class="mt-1"><span class="badge badge-info">{{ $user->account_type->label() }}</span></p>
                <p class="mt-4 text-xs font-medium text-ink-muted">Status</p>
                <p class="mt-1"><span class="badge bg-{{ $user->status->badgeColor() }}-50 text-{{ $user->status->badgeColor() }}-700">{{ $user->status->label() }}</span></p>
                <p class="mt-4 text-xs font-medium text-ink-muted">Member since</p>
                <p class="mt-1 text-sm text-ink">{{ $user->created_at->format('d M Y') }}</p>
            </div>
        </div>

        {{-- Password --}}
        <div class="card p-6 lg:col-span-2">
            <h2 class="font-semibold text-ink">Update password</h2>
            <p class="mt-1 text-sm text-ink-muted">Use a long, random password to keep your account secure.</p>

            <form method="POST" action="{{ route('password.update') }}" class="mt-5 space-y-5">
                @csrf @method('PUT')

                <div>
                    <label for="current_password" class="form-label">Current password</label>
                    <input id="current_password" name="current_password" type="password" class="form-input" autocomplete="current-password" />
                    <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
                </div>

                <div>
                    <label for="password" class="form-label">New password</label>
                    <input id="password" name="password" type="password" class="form-input" autocomplete="new-password" />
                    <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
                </div>

                <div>
                    <label for="password_confirmation" class="form-label">Confirm new password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" autocomplete="new-password" />
                    <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1" />
                </div>

                <button type="submit" class="btn-primary">Update password</button>
            </form>
        </div>

        {{-- Danger zone --}}
        <div class="card border-rose-100 p-6">
            <h2 class="font-semibold text-rose-600">Delete account</h2>
            <p class="mt-1 text-sm text-ink-muted">Once deleted, all of your data is permanently removed. This cannot be undone.</p>

            <form method="POST" action="{{ route('profile.destroy') }}" class="mt-5 space-y-4">
                @csrf @method('DELETE')

                <div>
                    <label for="password_delete" class="form-label">Confirm with your password</label>
                    <input id="password_delete" name="password" type="password" class="form-input" autocomplete="current-password" placeholder="Your password" />
                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1" />
                </div>

                <button type="submit" class="btn w-full border border-rose-200 text-rose-600 hover:bg-rose-50">Delete my account</button>
            </form>
        </div>
    </div>

</x-layouts.dashboard>
