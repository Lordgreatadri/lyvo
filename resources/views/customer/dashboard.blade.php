<x-layouts.dashboard role="customer" title="Customer Dashboard" :heading="'Welcome back, '.auth()->user()->name" subheading="Here's an overview of your trusted activity on LYVO.">

    {{-- KPI metrics --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($metrics as $metric)
            <x-stat-card :metric="$metric" />
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Recent orders --}}
        <div class="card overflow-hidden lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 p-5">
                <h2 class="font-semibold text-ink">Recent orders</h2>
                <a href="{{ route('customer.orders.index') }}" class="text-sm font-medium text-primary-600 hover:underline">View all</a>
            </div>
            @if ($recent->isEmpty())
                <div class="p-10 text-center">
                    <p class="text-sm text-ink-muted">You haven't placed any orders yet.</p>
                    <a href="{{ route('store.index') }}" class="btn-primary btn-sm mt-4">Browse marketplace</a>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($recent as $order)
                        @php $item = $order->items->first(); @endphp
                        <a href="{{ route('customer.orders.show', $order) }}" class="flex items-center gap-4 p-4 transition hover:bg-surface-muted">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-lyvo-gradient-soft text-primary-600"><x-icon name="shield" class="h-5 w-5" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-ink">{{ $item?->name ?? 'Order' }}</p>
                                <p class="truncate text-xs text-ink-muted">{{ $order->operator->business_name }} · {{ $order->order_number }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-ink">GH₵ {{ number_format((float) $order->total, 2) }}</p>
                                <x-order-status :status="$order->status" class="mt-1" />
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Operators + escrow protection --}}
        <div class="space-y-6">
            <div class="card p-5">
                <h2 class="font-semibold text-ink">Your operators</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($operators as $operator)
                        <a href="{{ route('directory.show', $operator) }}" class="flex items-center gap-3">
                            <div class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-primary-500 to-brand-teal text-xs font-bold text-white">
                                {{ \Illuminate\Support\Str::of($operator->business_name)->explode(' ')->map(fn ($p) => $p[0])->take(2)->implode('') }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink">{{ $operator->business_name }}</p>
                                <p class="text-xs text-ink-muted">Trust {{ $operator->trust_score }}</p>
                            </div>
                            <x-icon name="arrow-right" class="h-4 w-4 text-ink-muted" />
                        </a>
                    @empty
                        <p class="text-sm text-ink-muted">Operators you order from appear here.</p>
                        <a href="{{ route('directory.index') }}" class="btn-outline btn-sm mt-2">Explore operators</a>
                    @endforelse
                </div>
            </div>

            <div class="card p-5">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-lyvo-gradient-soft text-primary-600"><x-icon name="shield-check" class="h-5 w-5" /></span>
                    <div>
                        <h2 class="font-semibold text-ink">Escrow protection</h2>
                        <p class="text-xs text-ink-muted">Funds are only released when you confirm delivery.</p>
                    </div>
                </div>
                <a href="{{ route('escrow.index') }}" class="btn-outline btn-sm mt-4 w-full">View escrow</a>
            </div>
        </div>
    </div>

</x-layouts.dashboard>
