<x-layouts.dashboard role="operator" title="Operator Dashboard" :heading="$profile->business_name" subheading="Your verified business at a glance.">

    {{-- Verification + public page banner --}}
    <div class="mb-6 flex flex-col items-start gap-3 rounded-2xl bg-lyvo-gradient p-5 text-white sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-white/15"><x-icon name="shield-check" class="h-6 w-6" /></span>
            <div>
                <p class="font-semibold">You're a Verified Operator</p>
                <p class="text-sm text-white/80">Ghana Card · Identity · Video — all verified.</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('directory.show', $profile) }}" target="_blank" class="btn bg-white text-primary-700 hover:bg-white/90 btn-sm"><x-icon name="globe" class="h-4 w-4" /> View public page</a>
            <a href="{{ route('operator.branding.edit') }}" class="btn bg-white/15 text-white hover:bg-white/25 btn-sm"><x-icon name="sparkles" class="h-4 w-4" /> Branding</a>
            <a href="{{ route('operator.verification') }}" class="btn bg-white/15 text-white hover:bg-white/25 btn-sm">Verification</a>
        </div>
    </div>

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
                <a href="{{ route('operator.orders.index') }}" class="text-sm font-medium text-primary-600 hover:underline">Manage</a>
            </div>
            @if ($recent->isEmpty())
                <div class="p-10 text-center text-sm text-ink-muted">No orders yet — publish products to start selling.</div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($recent as $order)
                        @php $item = $order->items->first(); @endphp
                        <a href="{{ route('operator.orders.show', $order) }}" class="flex items-center gap-4 p-4 transition hover:bg-surface-muted">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-lyvo-gradient-soft text-primary-600"><x-icon name="wallet" class="h-5 w-5" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-ink">{{ $item?->name ?? 'Order' }}</p>
                                <p class="truncate text-xs text-ink-muted">{{ $order->order_number }} · {{ $order->customer->name }}</p>
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

        {{-- Trust score + products --}}
        <div class="space-y-6">
            <div class="card p-5 text-center">
                <p class="text-sm font-medium text-ink-muted">Trust Score</p>
                <p class="mt-2 font-display text-5xl font-extrabold text-gradient">{{ $profile->trust_score }}</p>
                <span class="badge-verified mt-2">Verified Operator</span>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-surface-muted">
                    <div class="h-full rounded-full bg-lyvo-gradient" style="width: {{ min(100, (int) $profile->trust_score) }}%"></div>
                </div>
            </div>

            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-ink">Products <span class="text-ink-muted">({{ $productCount }})</span></h2>
                    <a href="{{ route('operator.products.create') }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" /> Add</a>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($products as $product)
                        @php $thumb = $product->getFirstMediaUrl('images', 'thumb'); @endphp
                        <a href="{{ route('operator.products.edit', $product) }}" class="flex items-center gap-3">
                            <div class="h-10 w-10 overflow-hidden rounded-xl bg-lyvo-gradient-soft">
                                @if ($thumb)<img src="{{ $thumb }}" alt="" class="h-full w-full object-cover" />@endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink">{{ $product->name }}</p>
                                <p class="text-xs text-primary-600">GH₵ {{ number_format((float) $product->price, 2) }}</p>
                            </div>
                            <x-icon name="arrow-right" class="h-4 w-4 text-ink-muted" />
                        </a>
                    @empty
                        <p class="text-sm text-ink-muted">No products yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</x-layouts.dashboard>
