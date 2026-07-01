<x-layouts.dashboard role="admin" title="Roles &amp; Permissions" heading="Roles &amp; Permissions" subheading="Control what each role can do. Assign roles to users from their profile.">

    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('status') }}</div>
    @endif

    <div class="space-y-6">
        @foreach ($roles as $role)
            @php $assigned = $role->permissions->pluck('name')->all(); @endphp
            <div class="card p-6">
                <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                    @csrf @method('PUT')

                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-ink">{{ ucfirst($role->name) }}</h2>
                            <p class="text-xs text-ink-muted">{{ $role->users_count }} {{ Str::plural('user', $role->users_count) }} · {{ count($assigned) }} permissions</p>
                        </div>
                        <button type="submit" class="btn-primary btn-sm">Save {{ ucfirst($role->name) }}</button>
                    </div>

                    <div class="mt-4 grid gap-6 md:grid-cols-2">
                        @foreach ($groups as $groupLabel => $permissions)
                            <div>
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ $groupLabel }}</p>
                                <div class="space-y-2">
                                    @foreach ($permissions as $permission => $description)
                                        <label class="flex items-start gap-3 rounded-xl border border-slate-100 p-3 text-sm hover:border-primary-200">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, $assigned, true)) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-primary-600" />
                                            <span>
                                                <span class="font-medium text-ink">{{ $permission }}</span>
                                                <span class="block text-xs text-ink-muted">{{ $description }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <x-input-error :messages="$errors->get('permissions')" class="mt-2" />
                </form>
            </div>
        @endforeach
    </div>

</x-layouts.dashboard>
