<x-layouts.dashboard role="admin" title="Users" heading="User Management" subheading="Browse, approve, freeze and assign roles across every account.">

    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('status') }}</div>
    @endif

    {{-- Summary counters --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <div class="card p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Total</p>
            <p class="mt-1 text-2xl font-semibold text-ink">{{ $counts['total'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Operators</p>
            <p class="mt-1 text-2xl font-semibold text-ink">{{ $counts['operators'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Customers</p>
            <p class="mt-1 text-2xl font-semibold text-ink">{{ $counts['customers'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Frozen</p>
            <p class="mt-1 text-2xl font-semibold text-amber-600">{{ $counts['frozen'] }}</p>
        </div>
        <a href="{{ route('admin.users.index', ['pending' => 1]) }}" class="card p-4 transition hover:border-primary-200">
            <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Pending review</p>
            <p class="mt-1 text-2xl font-semibold text-primary-600">{{ $counts['pending'] }}</p>
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="card mb-6 flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-[12rem] flex-1">
            <label for="search" class="form-label">Search</label>
            <input id="search" type="text" name="search" value="{{ $filters['search'] }}" placeholder="Name, email or phone" class="form-input" />
        </div>
        <div>
            <label for="type" class="form-label">Account type</label>
            <select id="type" name="type" class="form-select">
                <option value="">All types</option>
                @foreach ($accountTypes as $type)
                    <option value="{{ $type->value }}" @selected($filters['type'] === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 pb-2 text-sm text-ink">
            <input type="checkbox" name="pending" value="1" @checked($filters['pending']) class="h-4 w-4 rounded border-slate-300 text-primary-600" />
            Pending operators
        </label>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary btn-sm">Filter</button>
            <a href="{{ route('admin.users.index') }}" class="btn-ghost btn-sm">Reset</a>
        </div>
    </form>

    {{-- User table --}}
    <div class="card overflow-hidden">
        <div class="divide-y divide-slate-100">
            @forelse ($users as $user)
                <div class="flex flex-wrap items-center gap-4 p-4">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-lyvo-gradient-soft text-primary-600">
                        <x-icon name="user" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-ink">{{ $user->name }}</p>
                        <p class="truncate text-xs text-ink-muted">{{ $user->email }} · {{ $user->phone }}</p>
                    </div>
                    <span class="badge badge-info">{{ $user->account_type->label() }}</span>
                    <span class="badge bg-{{ $user->status->badgeColor() }}-50 text-{{ $user->status->badgeColor() }}-700">
                        {{ $user->status->label() }}
                    </span>
                    @if ($user->roles->isNotEmpty())
                        <span class="hidden text-xs text-ink-muted sm:inline">{{ $user->roles->pluck('name')->join(', ') }}</span>
                    @endif
                    <a href="{{ route('admin.users.show', $user) }}" class="btn-outline btn-sm">Manage</a>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-ink-muted">No users match these filters.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

</x-layouts.dashboard>
