<x-layouts.dashboard role="admin" title="Operator Verification" heading="Verification Center" subheading="Review and approve operator applications.">

    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('status') }}</div>
    @endif

    <div class="card overflow-hidden">
        <div class="border-b border-slate-100 p-5">
            <h2 class="font-semibold text-ink">Pending applications</h2>
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

</x-layouts.dashboard>
