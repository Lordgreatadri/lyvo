<x-layouts.dashboard role="admin" title="Manage User" heading="{{ $user->name }}" subheading="Review account details, control access and assign roles.">

    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('status') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Left: profile + operator + roles --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Account details --}}
            <div class="card p-6">
                <h2 class="font-semibold text-ink">Account details</h2>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2 text-sm">
                    <div><dt class="text-ink-muted">Name</dt><dd class="font-medium text-ink">{{ $user->name }}</dd></div>
                    <div><dt class="text-ink-muted">Account type</dt><dd class="font-medium text-ink">{{ $user->account_type->label() }}</dd></div>
                    <div>
                        <dt class="text-ink-muted">Email</dt>
                        <dd class="font-medium text-ink">
                            {{ $user->email }}
                            @if ($user->email_verified_at)
                                <span class="badge badge-verified ml-1">Verified</span>
                            @else
                                <span class="badge bg-amber-50 text-amber-700 ml-1">Unverified</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-muted">Phone</dt>
                        <dd class="font-medium text-ink">
                            {{ $user->phone }}
                            @if ($user->phone_verified_at)
                                <span class="badge badge-verified ml-1">Verified</span>
                            @else
                                <span class="badge bg-amber-50 text-amber-700 ml-1">Unverified</span>
                            @endif
                        </dd>
                    </div>
                    <div><dt class="text-ink-muted">Joined</dt><dd class="font-medium text-ink">{{ $user->created_at->format('d M Y') }}</dd></div>
                    <div><dt class="text-ink-muted">Last updated</dt><dd class="font-medium text-ink">{{ $user->updated_at->diffForHumans() }}</dd></div>
                </dl>
            </div>

            {{-- Operator profile --}}
            @if ($user->operatorProfile)
                @php $profile = $user->operatorProfile; @endphp
                <div class="card p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold text-ink">Operator profile</h2>
                        <span class="badge bg-{{ $profile->verification_status->badgeColor() }}-50 text-{{ $profile->verification_status->badgeColor() }}-700">
                            {{ $profile->verification_status->label() }}
                        </span>
                    </div>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2 text-sm">
                        <div><dt class="text-ink-muted">Business</dt><dd class="font-medium text-ink">{{ $profile->business_name }}</dd></div>
                        <div><dt class="text-ink-muted">Category</dt><dd class="font-medium text-ink">{{ $profile->category?->name ?? '—' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-ink-muted">Location</dt><dd class="font-medium text-ink">{{ $profile->business_location }}</dd></div>
                    </dl>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('admin.operators.show', $profile) }}" class="btn-outline btn-sm">Open verification review</a>
                        @unless ($profile->isApproved())
                            <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn-primary btn-sm">Approve operator</button>
                            </form>
                        @endunless
                    </div>
                </div>

                {{-- Verification history --}}
                <div class="card p-6">
                    <h2 class="font-semibold text-ink">Verification history</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($profile->verificationEvents->sortByDesc('created_at') as $event)
                            <div class="flex items-start gap-3 text-sm">
                                <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-full bg-surface-muted text-ink-soft"><x-icon name="shield" class="h-3.5 w-3.5" /></span>
                                <div>
                                    <p class="text-ink"><span class="font-medium">{{ $event->from_status ?? 'new' }}</span> &rarr; <span class="font-medium">{{ $event->to_status }}</span></p>
                                    <p class="text-xs text-ink-muted">{{ $event->actor?->name ?? 'System' }} · {{ $event->created_at->diffForHumans() }}</p>
                                    @if ($event->notes)<p class="mt-1 text-xs text-ink-soft">{{ $event->notes }}</p>@endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-ink-muted">No history yet.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- Role assignment --}}
            <div class="card p-6">
                <h2 class="font-semibold text-ink">Roles</h2>
                <p class="mt-1 text-sm text-ink-muted">Roles bundle the permissions this user is granted. Manage each role's permissions under <a href="{{ route('admin.roles.index') }}" class="text-primary-600 hover:underline">Roles</a>.</p>
                <form method="POST" action="{{ route('admin.users.roles', $user) }}" class="mt-4">
                    @csrf @method('PUT')
                    @php $assigned = $user->roles->pluck('name')->all(); @endphp
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($roles as $role)
                            <label class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 text-sm hover:border-primary-200">
                                <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(in_array($role->name, $assigned, true)) class="h-4 w-4 rounded border-slate-300 text-primary-600" />
                                <span>
                                    <span class="font-medium text-ink">{{ ucfirst($role->name) }}</span>
                                    <span class="block text-xs text-ink-muted">{{ $role->permissions->count() }} permissions</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('roles')" class="mt-2" />
                    <button type="submit" class="btn-primary btn-sm mt-4">Save roles</button>
                </form>
            </div>
        </div>

        {{-- Right: status + access controls --}}
        <div class="space-y-4">
            <div class="card p-5">
                <p class="text-xs font-medium text-ink-muted">Account status</p>
                <p class="mt-1">
                    <span class="badge bg-{{ $user->status->badgeColor() }}-50 text-{{ $user->status->badgeColor() }}-700">
                        {{ $user->status->label() }}
                    </span>
                </p>
                <p class="mt-2 text-xs text-ink-soft">{{ $user->status->description() }}</p>

                @if ($user->isNot(auth()->user()))
                    @if ($user->isFrozen())
                        <form method="POST" action="{{ route('admin.users.unfreeze', $user) }}" class="mt-4">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn-primary w-full">Reactivate account</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.users.freeze', $user) }}" class="mt-4">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn w-full border border-amber-200 text-amber-700 hover:bg-amber-50">Freeze account</button>
                        </form>
                    @endif
                @else
                    <p class="mt-4 rounded-xl bg-surface-muted p-3 text-xs text-ink-muted">You cannot freeze or change roles on your own account.</p>
                @endif
            </div>

            <a href="{{ route('admin.users.index') }}" class="btn-ghost w-full justify-center">Back to users</a>
        </div>
    </div>

</x-layouts.dashboard>
