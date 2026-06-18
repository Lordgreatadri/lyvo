<x-layouts.dashboard role="customer" title="Escrow Transaction" heading="Escrow Transaction" :subheading="$transaction['ref']">

    <a href="{{ route('escrow.index') }}" class="mb-6 inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-ink">
        <x-icon name="arrow-right" class="h-4 w-4 rotate-180" /> Back to escrow
    </a>

    @php
        // Determine which pipeline stages are completed based on current status.
        $order = ['initiated', 'held', 'processing', 'delivered', 'released'];
        $currentIndex = array_search($transaction['status_key'], $order);
    @endphp

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Timeline --}}
        <div class="card p-6 lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-ink">{{ $transaction['item'] }}</h2>
                <x-escrow-status :status="$transaction['status']" :key="$transaction['status_key']" />
            </div>
            <p class="mt-1 text-sm text-ink-muted">{{ $transaction['operator'] }}</p>

            <div class="mt-8 space-y-1">
                @foreach ($pipeline as $i => $step)
                    @php
                        $stepIndex = array_search($step['key'], $order);
                        $done = $stepIndex < $currentIndex;
                        $current = $stepIndex === $currentIndex;
                    @endphp
                    <div class="flex items-start gap-4">
                        <div class="flex flex-col items-center">
                            @if ($done)
                                <span class="grid h-9 w-9 place-items-center rounded-full bg-primary-500 text-white"><x-icon name="check" class="h-4 w-4" /></span>
                            @elseif ($current)
                                <span class="relative grid h-9 w-9 place-items-center rounded-full bg-lyvo-gradient text-white">
                                    <x-icon name="shield" class="h-4 w-4" />
                                    <span class="absolute inset-0 animate-pulse-ring rounded-full bg-primary-400"></span>
                                </span>
                            @else
                                <span class="grid h-9 w-9 place-items-center rounded-full border-2 border-slate-200"></span>
                            @endif
                            @if (! $loop->last)
                                <span class="h-12 w-0.5 {{ $done ? 'bg-primary-500' : 'bg-slate-200' }}"></span>
                            @endif
                        </div>
                        <div class="pb-8 pt-1">
                            <p class="font-medium {{ ($done || $current) ? 'text-ink' : 'text-ink-muted' }}">{{ $step['label'] }}</p>
                            <p class="text-sm text-ink-muted">{{ $step['desc'] }}</p>
                            @if ($current)
                                <span class="badge-pending mt-2">Current stage</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Summary + actions --}}
        <div class="space-y-6">
            <div class="card overflow-hidden">
                <div class="bg-ink p-5 text-white">
                    <p class="text-xs text-slate-400">Amount in escrow</p>
                    <p class="mt-1 font-display text-3xl font-extrabold">GH₵ {{ number_format($transaction['amount'], 2) }}</p>
                    <div class="mt-3 flex items-center gap-2 text-xs text-primary-300">
                        <x-icon name="lock" class="h-4 w-4" /> Protected by LYVO Escrow
                    </div>
                </div>
                <div class="space-y-3 p-5 text-sm">
                    <div class="flex justify-between"><span class="text-ink-muted">Reference</span><span class="font-medium text-ink">{{ $transaction['ref'] }}</span></div>
                    <div class="flex justify-between"><span class="text-ink-muted">Operator</span><span class="font-medium text-ink">{{ $transaction['operator'] }}</span></div>
                    <div class="flex justify-between"><span class="text-ink-muted">Date</span><span class="font-medium text-ink">{{ $transaction['date'] }}</span></div>
                    <div class="flex justify-between"><span class="text-ink-muted">Payment</span><span class="font-medium text-ink">Moolre · Mobile Money</span></div>
                </div>
            </div>

            {{-- Buyer actions --}}
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-ink">Buyer Actions</h3>
                <div class="mt-4 space-y-2">
                    <button class="btn-primary w-full"><x-icon name="check-circle" class="h-4 w-4" /> Confirm Delivery</button>
                    <button class="btn-outline w-full text-rose-600 hover:border-rose-300"><x-icon name="flag" class="h-4 w-4" /> Raise Dispute</button>
                </div>
                <p class="mt-3 text-xs text-ink-muted">Funds are only released to the operator after you confirm delivery.</p>
            </div>

            {{-- Operator actions (context for demo) --}}
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-ink">Operator Actions</h3>
                <div class="mt-4 space-y-2">
                    <button class="btn-outline w-full">Mark Processing</button>
                    <button class="btn-dark w-full">Mark Delivered</button>
                </div>
            </div>
        </div>
    </div>

</x-layouts.dashboard>
