<x-layouts.dashboard role="admin" title="Operator Verification" heading="Verification Center" subheading="Review and approve operator applications.">

    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('status') }}</div>
    @endif

    {{-- Summary --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Total operators</p>
            <p class="mt-1 font-display text-2xl font-bold text-ink">{{ $summary['total'] }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Operational</p>
            <p class="mt-1 font-display text-2xl font-bold text-emerald-600">{{ $summary['approved'] }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Awaiting review</p>
            <p class="mt-1 font-display text-2xl font-bold text-amber-600">{{ $summary['pending'] }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Rejected</p>
            <p class="mt-1 font-display text-2xl font-bold text-rose-600">{{ $summary['rejected'] }}</p>
        </div>
    </div>

    {{-- Pending applications --}}
    <div class="card overflow-hidden">
        <div class="border-b border-slate-100 p-5">
            <h2 class="font-semibold text-ink">Pending applications</h2>
            <p class="text-xs text-ink-muted">Operators awaiting your approval before they can trade.</p>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($pending as $operator)
                <div class="flex items-center gap-4 p-4">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-lyvo-gradient-soft text-primary-600"><x-icon name="badge" class="h-5 w-5" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-ink">{{ $operator->business_name }}</p>
                        <p class="truncate text-xs text-ink-muted">
                            {{ $operator->owner_full_name }} · {{ $operator->category?->name ?? 'Uncategorised' }} · {{ $operator->submitted_at?->diffForHumans() }}
                        </p>
                    </div>
                    <span class="badge bg-{{ $operator->verification_status->badgeColor() }}-50 text-{{ $operator->verification_status->badgeColor() }}-700">
                        {{ $operator->verification_status->label() }}
                    </span>
                    <a href="{{ route('admin.operators.show', $operator) }}" class="btn-outline btn-sm">Review</a>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-ink-muted">No pending applications. 🎉</div>
            @endforelse
        </div>
    </div>

    {{-- Full operator list --}}
    <div class="card mt-6 overflow-hidden">
        <div class="border-b border-slate-100 p-5">
            <h2 class="font-semibold text-ink">All operators</h2>
            <p class="text-xs text-ink-muted">Every operator on the platform and their current standing.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-surface-muted text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        <th class="px-5 py-3">Business</th>
                        <th class="px-5 py-3">Category</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-center">Products</th>
                        <th class="px-5 py-3 text-center">Trust</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($operators as $operator)
                        <tr class="hover:bg-surface-muted/50">
                            <td class="px-5 py-3">
                                <p class="font-medium text-ink">{{ $operator->business_name }}</p>
                                <p class="text-xs text-ink-muted">{{ $operator->user?->name ?? $operator->owner_full_name }}</p>
                            </td>
                            <td class="px-5 py-3 text-ink-soft">{{ $operator->category?->name ?? 'Uncategorised' }}</td>
                            <td class="px-5 py-3">
                                <span class="badge bg-{{ $operator->verification_status->badgeColor() }}-50 text-{{ $operator->verification_status->badgeColor() }}-700">
                                    {{ $operator->verification_status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center text-ink-soft">{{ $operator->published_products_count }}</td>
                            <td class="px-5 py-3 text-center font-medium text-ink">{{ $operator->trust_score }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.operators.show', $operator) }}" class="btn-outline btn-sm">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-sm text-ink-muted">No operators registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-layouts.dashboard>
