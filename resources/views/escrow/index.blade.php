<x-layouts.dashboard role="customer" title="Escrow" heading="Escrow Transactions" subheading="Every payment is held securely until you confirm delivery.">

    {{-- Status summary --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Funds Held', 'GH₵ 850', 'shield', 'text-indigo-600'],
            ['Processing', 'GH₵ 540', 'box', 'text-amber-600'],
            ['Delivered', 'GH₵ 320', 'check-circle', 'text-sky-600'],
            ['Released', 'GH₵ 260', 'wallet', 'text-primary-600'],
        ] as [$label, $value, $icon, $color])
            <div class="card p-5">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-surface-muted {{ $color }}"><x-icon name="{{ $icon }}" class="h-5 w-5" /></span>
                <p class="mt-3 font-display text-xl font-bold text-ink">{{ $value }}</p>
                <p class="text-sm text-ink-muted">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 p-5">
            <h2 class="font-semibold text-ink">All Transactions</h2>
            <div class="flex gap-2">
                @foreach (['All', 'Pending', 'Delivered', 'Released'] as $i => $tab)
                    <button class="badge {{ $i === 0 ? 'bg-ink text-white' : 'bg-surface-muted text-ink-soft hover:bg-slate-100' }}">{{ $tab }}</button>
                @endforeach
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            @foreach ($transactions as $tx)
                <a href="{{ route('escrow.show', $tx['uuid']) }}" class="flex items-center gap-4 p-4 transition hover:bg-surface-muted">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-lyvo-gradient-soft text-primary-600"><x-icon name="shield" class="h-5 w-5" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-ink">{{ $tx['item'] }}</p>
                        <p class="truncate text-xs text-ink-muted">{{ $tx['operator'] }} · {{ $tx['ref'] }} · {{ $tx['date'] }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-ink">GH₵ {{ number_format($tx['amount'], 2) }}</p>
                        <x-escrow-status :status="$tx['status']" :key="$tx['status_key']" />
                    </div>
                    <x-icon name="arrow-right" class="hidden h-4 w-4 text-ink-muted sm:block" />
                </a>
            @endforeach
        </div>
    </div>

</x-layouts.dashboard>
