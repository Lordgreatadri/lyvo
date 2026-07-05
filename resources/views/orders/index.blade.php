@php
    $escrow = $escrow ?? false;
    $filter = $filter ?? null;
    $showRoute = [
        'customer' => 'customer.orders.show',
        'operator' => 'operator.orders.show',
        'admin'    => 'admin.orders.show',
    ][$role];

    $headings = [
        'customer' => $escrow ? 'Your escrow' : 'My orders',
        'operator' => $escrow ? 'Funds in escrow' : 'Orders',
        'admin'    => $escrow ? 'Funds held platform-wide' : 'Escrow oversight',
    ];
    $subs = [
        'customer' => $escrow ? 'Payments held securely until you confirm delivery.' : 'Track every order and its escrow lifecycle.',
        'operator' => $escrow ? 'Money awaiting release once buyers confirm delivery.' : 'Fulfil paid orders and keep buyers updated.',
        'admin'    => $escrow ? 'Every order with funds currently held in escrow.' : 'Monitor every order and resolve disputes.',
    ];
@endphp

<x-layouts.dashboard :role="$role" title="Orders" :heading="$headings[$role]" :subheading="$subs[$role]">

    @if (session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ session('error') }}</div>
    @endif

    {{-- Summary cards --}}
    @if (! empty($summary))
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $cards = match ($role) {
                    'customer' => [
                        ['Active escrows', $summary['active'], 'shield', 'text-indigo-600'],
                        ['Protected now', 'GH₵ '.number_format((float) $summary['protected'], 2), 'lock', 'text-primary-600'],
                        ['Completed', $summary['completed'], 'check-circle', 'text-emerald-600'],
                        ['Total orders', $summary['total'], 'box', 'text-sky-600'],
                    ],
                    'operator' => [
                        ['Active orders', $summary['active'], 'box', 'text-amber-600'],
                        ['In escrow', 'GH₵ '.number_format((float) $summary['in_escrow'], 2), 'shield', 'text-indigo-600'],
                        ['Released', 'GH₵ '.number_format((float) $summary['released'], 2), 'wallet', 'text-primary-600'],
                        ['Total orders', $summary['total'], 'chart', 'text-sky-600'],
                    ],
                    'admin' => [
                        ['Held in escrow', 'GH₵ '.number_format((float) $summary['in_escrow'], 2), 'shield', 'text-indigo-600'],
                        ['Open disputes', $summary['disputes'], 'flag', 'text-rose-600'],
                        ['Released', 'GH₵ '.number_format((float) $summary['released'], 2), 'wallet', 'text-primary-600'],
                        ['Total orders', $summary['total'], 'chart', 'text-sky-600'],
                    ],
                };
            @endphp
            @foreach ($cards as [$label, $value, $icon, $color])
                <div class="card p-5">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-surface-muted {{ $color }}"><x-icon name="{{ $icon }}" class="h-5 w-5" /></span>
                    <p class="mt-3 font-display text-xl font-bold text-ink">{{ $value }}</p>
                    <p class="text-sm text-ink-muted">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Admin filter tabs --}}
    @if ($role === 'admin' && ! $escrow)
        <div class="mb-4 flex gap-2">
            @foreach (['' => 'All', 'escrow' => 'In escrow', 'disputed' => 'Disputes'] as $key => $tab)
                <a href="{{ route('admin.orders.index', array_filter(['filter' => $key])) }}"
                   class="badge {{ $filter === ($key ?: null) ? 'bg-ink text-white' : 'bg-surface-muted text-ink-soft hover:bg-slate-100' }}">{{ $tab }}</a>
            @endforeach
        </div>
    @endif

    <div class="card overflow-hidden">
        @if ($orders->isEmpty())
            <div class="p-12 text-center">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-surface-muted text-ink-muted"><x-icon name="box" class="h-7 w-7" /></span>
                <p class="mt-4 font-semibold text-ink">No orders yet</p>
                <p class="mt-1 text-sm text-ink-muted">
                    @if ($role === 'customer')
                        Browse the marketplace and pay securely with escrow.
                    @else
                        Orders will appear here as customers buy from you.
                    @endif
                </p>
                @if ($role === 'customer')
                    <a href="{{ route('store.index') }}" class="btn-primary btn-sm mt-4">Browse marketplace</a>
                @endif
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($orders as $order)
                    @php $item = $order->items->first(); @endphp
                    <a href="{{ route($showRoute, $order) }}" class="flex items-center gap-4 p-4 transition hover:bg-surface-muted">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-lyvo-gradient-soft text-primary-600"><x-icon name="shield" class="h-5 w-5" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-ink">{{ $item?->name ?? 'Order' }}@if ($order->items->count() > 1) <span class="text-ink-muted">+{{ $order->items->count() - 1 }}</span>@endif</p>
                            <p class="truncate text-xs text-ink-muted">
                                {{ $order->order_number }}
                                @if ($role === 'customer') · {{ $order->operator->business_name }}
                                @elseif ($role === 'operator') · {{ $order->customer->name }}
                                @else · {{ $order->customer->name }} → {{ $order->operator->business_name }}
                                @endif
                                · {{ $order->created_at->format('M d, Y') }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-ink">GH₵ {{ number_format((float) $order->total, 2) }}</p>
                            <x-order-status :status="$order->status" class="mt-1" />
                        </div>
                        <x-icon name="arrow-right" class="hidden h-4 w-4 text-ink-muted sm:block" />
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($orders->hasPages())
        <div class="mt-6">{{ $orders->links() }}</div>
    @endif

</x-layouts.dashboard>
