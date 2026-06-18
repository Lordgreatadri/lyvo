<x-layouts.dashboard role="operator" title="Operator Dashboard" heading="Adwoa Couture" subheading="Your verified business at a glance.">

    {{-- Verification banner --}}
    <div class="mb-6 flex flex-col items-start gap-3 rounded-2xl bg-lyvo-gradient p-5 text-white sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-white/15"><x-icon name="shield-check" class="h-6 w-6" /></span>
            <div>
                <p class="font-semibold">You're a Verified Operator</p>
                <p class="text-sm text-white/80">Ghana Card · Identity · Video — all verified.</p>
            </div>
        </div>
        <a href="{{ route('operator.verification') }}" class="btn bg-white text-primary-700 hover:bg-white/90 btn-sm">View verification</a>
    </div>

    {{-- KPI metrics --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($metrics as $metric)
            <x-stat-card :metric="$metric" />
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Escrow / held funds --}}
        <div class="card overflow-hidden lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 p-5">
                <h2 class="font-semibold text-ink">Escrow Transactions</h2>
                <a href="{{ route('escrow.index') }}" class="text-sm font-medium text-primary-600 hover:underline">Manage</a>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($transactions as $tx)
                    <div class="flex items-center gap-4 p-4">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-lyvo-gradient-soft text-primary-600"><x-icon name="wallet" class="h-5 w-5" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-ink">{{ $tx['item'] }}</p>
                            <p class="truncate text-xs text-ink-muted">{{ $tx['ref'] }} · {{ $tx['date'] }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <p class="text-sm font-semibold text-ink">GH₵ {{ number_format($tx['amount'], 2) }}</p>
                            @if ($tx['status_key'] === 'held')
                                <a href="{{ route('escrow.show', $tx['uuid']) }}" class="btn-primary btn-sm">Mark Processing</a>
                            @elseif ($tx['status_key'] === 'processing')
                                <a href="{{ route('escrow.show', $tx['uuid']) }}" class="btn-outline btn-sm">Mark Delivered</a>
                            @else
                                <x-escrow-status :status="$tx['status']" :key="$tx['status_key']" />
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Trust score + products --}}
        <div class="space-y-6">
            <div class="card p-5 text-center">
                <p class="text-sm font-medium text-ink-muted">Trust Score</p>
                <p class="mt-2 font-display text-5xl font-extrabold text-gradient">96</p>
                <span class="badge-verified mt-2">Trusted Operator</span>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-surface-muted">
                    <div class="h-full w-[96%] rounded-full bg-lyvo-gradient"></div>
                </div>
            </div>

            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-ink">Products</h2>
                    <button class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" /> Add</button>
                </div>
                <div class="mt-4 space-y-3">
                    @foreach (array_slice($products, 0, 3) as $product)
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-gradient-to-br {{ $product['image_bg'] }}"></div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink">{{ $product['name'] }}</p>
                                <p class="text-xs text-primary-600">GH₵ {{ number_format($product['price'], 2) }}</p>
                            </div>
                            <button class="text-ink-muted hover:text-ink"><x-icon name="settings" class="h-4 w-4" /></button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</x-layouts.dashboard>
