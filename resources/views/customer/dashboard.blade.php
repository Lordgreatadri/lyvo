<x-layouts.dashboard role="customer" title="Customer Dashboard" heading="Welcome back, Nana 👋" subheading="Here's an overview of your trusted activity on LYVO.">

    {{-- KPI metrics --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($metrics as $metric)
            <x-stat-card :metric="$metric" />
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Escrow transactions --}}
        <div class="card overflow-hidden lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 p-5">
                <h2 class="font-semibold text-ink">Escrow Transactions</h2>
                <a href="{{ route('escrow.index') }}" class="text-sm font-medium text-primary-600 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($transactions as $tx)
                    <a href="{{ route('escrow.show', $tx['uuid']) }}" class="flex items-center gap-4 p-4 transition hover:bg-surface-muted">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-lyvo-gradient-soft text-primary-600"><x-icon name="shield" class="h-5 w-5" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-ink">{{ $tx['item'] }}</p>
                            <p class="truncate text-xs text-ink-muted">{{ $tx['operator'] }} · {{ $tx['ref'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-ink">GH₵ {{ number_format($tx['amount'], 2) }}</p>
                            <x-escrow-status :status="$tx['status']" :key="$tx['status_key']" />
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Saved operators + trust activity --}}
        <div class="space-y-6">
            <div class="card p-5">
                <h2 class="font-semibold text-ink">Saved Operators</h2>
                <div class="mt-4 space-y-3">
                    @foreach (array_slice($operators, 0, 3) as $operator)
                        <a href="{{ route('directory.show', $operator['uuid']) }}" class="flex items-center gap-3">
                            <div class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br {{ $operator['logo_bg'] }} text-xs font-bold text-white">
                                {{ \Illuminate\Support\Str::of($operator['name'])->explode(' ')->map(fn ($p) => $p[0])->take(2)->implode('') }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink">{{ $operator['name'] }}</p>
                                <p class="text-xs text-ink-muted">Trust {{ $operator['trust_score'] }}</p>
                            </div>
                            <x-icon name="arrow-right" class="h-4 w-4 text-ink-muted" />
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="card p-5">
                <h2 class="font-semibold text-ink">Trust Activity</h2>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between"><span class="text-ink-soft">Reviews posted</span><span class="font-semibold text-ink">7</span></div>
                    <div class="flex items-center justify-between"><span class="text-ink-soft">Reports submitted</span><span class="font-semibold text-ink">1</span></div>
                    <div class="flex items-center justify-between"><span class="text-ink-soft">Operators saved</span><span class="font-semibold text-ink">12</span></div>
                </div>
            </div>
        </div>
    </div>

</x-layouts.dashboard>
