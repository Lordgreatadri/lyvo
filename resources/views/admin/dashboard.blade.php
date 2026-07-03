<x-layouts.dashboard role="admin" title="Admin Dashboard" heading="Platform Overview" subheading="Monitor verification, escrow, users and fraud across LYVO.">

    {{-- KPI metrics --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($metrics as $metric)
            <x-stat-card :metric="$metric" />
        @endforeach
    </div>

    {{-- Payments (Moolre) performance overview --}}
    <section class="mt-8">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-ink">Payments &middot; Moolre</h2>
                <p class="text-xs text-ink-muted">Live collection performance across the platform</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($payments['metrics'] as $metric)
                <x-stat-card :metric="$metric" />
            @endforeach
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            {{-- Recent transactions --}}
            <div class="card overflow-hidden lg:col-span-2">
                <div class="flex items-center justify-between border-b border-slate-100 p-5">
                    <div>
                        <h3 class="font-semibold text-ink">Recent transactions</h3>
                        <p class="text-xs text-ink-muted">Latest {{ $payments['recent']->count() }} collections</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($payments['recent'] as $txn)
                        @php
                            $badge = [
                                'amber'   => 'bg-amber-50 text-amber-700',
                                'sky'     => 'bg-sky-50 text-sky-700',
                                'emerald' => 'bg-emerald-50 text-emerald-700',
                                'rose'    => 'bg-rose-50 text-rose-700',
                            ][$txn->status->color()] ?? 'bg-slate-100 text-slate-600';
                        @endphp
                        <div class="flex items-center gap-4 p-4">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-lyvo-gradient-soft text-primary-600">
                                <x-icon name="wallet" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-ink">
                                    {{ $txn->currency }} {{ number_format((float) $txn->amount, 2) }}
                                    <span class="font-normal text-ink-muted">&middot; {{ $txn->payer }}</span>
                                </p>
                                <p class="truncate text-xs text-ink-muted">
                                    {{ $txn->channel?->label() ?? '—' }} &middot; {{ ucfirst($txn->context) }} &middot; {{ $txn->created_at?->diffForHumans() }}
                                </p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $badge }}">{{ $txn->status->label() }}</span>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm text-ink-muted">No payments recorded yet.</div>
                    @endforelse
                </div>
            </div>

            {{-- Channel breakdown --}}
            <div class="card p-5">
                <h3 class="font-semibold text-ink">Collections by channel</h3>
                <p class="text-xs text-ink-muted">Successful volume per network</p>
                <div class="mt-4 space-y-3 text-sm">
                    @forelse ($payments['channels'] as $channel)
                        <div class="flex items-center justify-between">
                            <span class="text-ink-soft">{{ $channel['label'] }}</span>
                            <span class="text-right">
                                <span class="font-semibold text-ink">{{ $channel['volume'] }}</span>
                                <span class="ml-1 text-xs text-ink-muted">({{ $channel['count'] }})</span>
                            </span>
                        </div>
                    @empty
                        <p class="text-ink-muted">No settled collections yet.</p>
                    @endforelse
                </div>
                <div class="mt-5 border-t border-slate-100 pt-4 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-ink-soft">Total transactions</span>
                        <span class="font-semibold text-ink">{{ number_format($payments['totals']['total']) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Verification queue --}}
        <div class="card overflow-hidden lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 p-5">
                <div>
                    <h2 class="font-semibold text-ink">Verification Center</h2>
                    <p class="text-xs text-ink-muted">Pending business applications</p>
                </div>
                <a href="{{ route('admin.verification') }}" class="btn-outline btn-sm">Open center</a>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($queue as $item)
                    <div class="flex items-center gap-4 p-4">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-surface-muted text-sm font-bold text-ink">
                            {{ \Illuminate\Support\Str::of($item['business'])->explode(' ')->map(fn ($p) => $p[0])->take(2)->implode('') }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-ink">{{ $item['business'] }}</p>
                            <p class="truncate text-xs text-ink-muted">{{ $item['owner'] }} · {{ $item['category'] }} · {{ $item['submitted'] }}</p>
                        </div>
                        @php
                            $riskBadge = ['low' => 'badge-verified', 'medium' => 'badge-pending', 'high' => 'badge-rejected'][$item['risk']];
                        @endphp
                        <span class="{{ $riskBadge }}">{{ ucfirst($item['risk']) }} risk</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Right column --}}
        <div class="space-y-6">
            <div class="card p-5">
                <h2 class="font-semibold text-ink">Escrow Monitor</h2>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between"><span class="flex items-center gap-2 text-ink-soft"><span class="h-2 w-2 rounded-full bg-indigo-500"></span> Held funds</span><span class="font-semibold text-ink">GH₵ 182k</span></div>
                    <div class="flex items-center justify-between"><span class="flex items-center gap-2 text-ink-soft"><span class="h-2 w-2 rounded-full bg-primary-500"></span> Released</span><span class="font-semibold text-ink">GH₵ 300k</span></div>
                    <div class="flex items-center justify-between"><span class="flex items-center gap-2 text-ink-soft"><span class="h-2 w-2 rounded-full bg-rose-500"></span> Disputes</span><span class="font-semibold text-ink">6</span></div>
                </div>
            </div>

            <div class="card p-5">
                <h2 class="font-semibold text-ink">Fraud Monitoring</h2>
                <div class="mt-4 space-y-3">
                    @foreach ([['QuickFix Repairs', 'Failed Ghana Card check', 'high'], ['GadgetPlug', 'Missing video verification', 'medium']] as [$name, $reason, $risk])
                        @php $riskBadge = ['high' => 'badge-rejected', 'medium' => 'badge-pending'][$risk]; @endphp
                        <div class="flex items-center justify-between rounded-xl bg-surface-muted p-3">
                            <div>
                                <p class="text-sm font-medium text-ink">{{ $name }}</p>
                                <p class="text-xs text-ink-muted">{{ $reason }}</p>
                            </div>
                            <span class="{{ $riskBadge }}">{{ ucfirst($risk) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</x-layouts.dashboard>
