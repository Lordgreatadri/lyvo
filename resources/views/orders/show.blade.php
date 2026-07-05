@php
    use App\Enums\OrderStatus;

    $indexRoute = [
        'customer' => 'customer.orders.index',
        'operator' => 'operator.orders.index',
        'admin'    => 'admin.orders.index',
    ][$role];

    $pipeline = [
        ['key' => 'initiated',  'label' => 'Payment initiated', 'desc' => 'Secure payment started'],
        ['key' => 'held',       'label' => 'Funds held',        'desc' => 'Money held safely in escrow'],
        ['key' => 'processing', 'label' => 'Seller processing',  'desc' => 'Operator prepares the order'],
        ['key' => 'delivered',  'label' => 'Delivered',          'desc' => 'Marked delivered by seller'],
        ['key' => 'released',   'label' => 'Funds released',     'desc' => 'Buyer confirmed — seller paid'],
    ];
    $currentKey = $order->status->pipelineKey();
@endphp

<x-layouts.dashboard :role="$role" title="Order {{ $order->order_number }}" heading="Order {{ $order->order_number }}" :subheading="$order->status->label()">

    @if (session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ session('error') }}</div>
    @endif

    <a href="{{ route($indexRoute) }}" class="mb-6 inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-ink">
        <x-icon name="arrow-right" class="h-4 w-4 rotate-180" /> Back to orders
    </a>

    @if ($order->status === OrderStatus::Disputed)
        <div class="mb-6 flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <x-icon name="flag" class="h-5 w-5 shrink-0" /> This order is under dispute and awaiting admin review.
        </div>
    @elseif ($order->status === OrderStatus::Refunded)
        <div class="mb-6 flex items-center gap-3 rounded-2xl border border-slate-200 bg-surface-muted px-4 py-3 text-sm text-ink-soft">
            <x-icon name="wallet" class="h-5 w-5 shrink-0" /> This order was refunded to the buyer.
        </div>
    @elseif ($order->status === OrderStatus::Cancelled)
        <div class="mb-6 flex items-center gap-3 rounded-2xl border border-slate-200 bg-surface-muted px-4 py-3 text-sm text-ink-soft">
            <x-icon name="flag" class="h-5 w-5 shrink-0" /> This order was cancelled.
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Timeline + items --}}
        <div class="space-y-6 lg:col-span-2">
            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-ink">Escrow lifecycle</h2>
                    <x-order-status :status="$order->status" />
                </div>

                @php
                    $stages = ['initiated','held','processing','delivered','released'];
                    $currentIndex = array_search($currentKey, $stages);
                    $terminalReleased = in_array($order->status, [OrderStatus::Released, OrderStatus::Refunded], true);
                @endphp

                <div class="mt-8 space-y-1">
                    @foreach ($pipeline as $step)
                        @php
                            $stepIndex = array_search($step['key'], $stages);
                            $done = $stepIndex < $currentIndex || ($terminalReleased && $step['key'] !== 'released') ;
                            $current = $stepIndex === $currentIndex && ! $terminalReleased;
                            $doneOrReleased = $done || ($terminalReleased && $step['key'] === 'released');
                        @endphp
                        <div class="flex items-start gap-4">
                            <div class="flex flex-col items-center">
                                @if ($doneOrReleased)
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
                                    <span class="h-12 w-0.5 {{ $doneOrReleased ? 'bg-primary-500' : 'bg-slate-200' }}"></span>
                                @endif
                            </div>
                            <div class="pb-8 pt-1">
                                <p class="font-medium {{ ($doneOrReleased || $current) ? 'text-ink' : 'text-ink-muted' }}">{{ $step['label'] }}</p>
                                <p class="text-sm text-ink-muted">{{ $step['desc'] }}</p>
                                @if ($current)
                                    <span class="badge-pending mt-2">Current stage</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Items --}}
            <div class="card p-6">
                <h2 class="font-semibold text-ink">Items</h2>
                <div class="mt-4 divide-y divide-slate-100">
                    @foreach ($order->items as $line)
                        <div class="flex items-center gap-4 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink">{{ $line->name }}</p>
                                <p class="text-xs text-ink-muted">{{ $line->quantity }} × GH₵ {{ number_format((float) $line->unit_price, 2) }}</p>
                            </div>
                            <p class="text-sm font-semibold text-ink">GH₵ {{ number_format((float) $line->line_total, 2) }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4">
                    <span class="font-semibold text-ink">Total</span>
                    <span class="font-display text-lg font-bold text-primary-700">GH₵ {{ number_format((float) $order->total, 2) }}</span>
                </div>
            </div>

            {{-- Audit trail --}}
            @if ($order->events->isNotEmpty())
                <div class="card p-6">
                    <h2 class="font-semibold text-ink">Activity</h2>
                    <div class="mt-4 space-y-4">
                        @foreach ($order->events as $event)
                            <div class="flex gap-3 text-sm">
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-primary-400"></span>
                                <div>
                                    <p class="text-ink">{{ $event->note }}</p>
                                    <p class="text-xs text-ink-muted">
                                        {{ $event->to_status->label() }}
                                        @if ($event->actor) · {{ $event->actor->name }} @endif
                                        · {{ $event->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Summary + actions --}}
        <div class="space-y-6">
            <div class="card overflow-hidden">
                <div class="bg-ink p-5 text-white">
                    <p class="text-xs text-slate-400">{{ $order->status->isEscrowHeld() ? 'Amount in escrow' : 'Order total' }}</p>
                    <p class="mt-1 font-display text-3xl font-extrabold">GH₵ {{ number_format((float) $order->total, 2) }}</p>
                    <div class="mt-3 flex items-center gap-2 text-xs text-primary-300">
                        <x-icon name="lock" class="h-4 w-4" /> Protected by LYVO Escrow
                    </div>
                </div>
                <div class="space-y-3 p-5 text-sm">
                    <div class="flex justify-between"><span class="text-ink-muted">Reference</span><span class="font-medium text-ink">{{ $order->order_number }}</span></div>
                    @if ($role !== 'customer')
                        <div class="flex justify-between"><span class="text-ink-muted">Buyer</span><span class="font-medium text-ink">{{ $order->customer->name }}</span></div>
                    @endif
                    @if ($role !== 'operator')
                        <div class="flex justify-between"><span class="text-ink-muted">Seller</span><span class="font-medium text-ink">{{ $order->operator->business_name }}</span></div>
                    @endif
                    <div class="flex justify-between"><span class="text-ink-muted">Placed</span><span class="font-medium text-ink">{{ $order->created_at->format('M d, Y') }}</span></div>
                    @if ($order->payment)
                        <div class="flex justify-between"><span class="text-ink-muted">Payment</span><span class="font-medium text-ink">{{ $order->payment->status->value === 'successful' ? 'Paid' : ucfirst($order->payment->status->value) }}</span></div>
                    @endif
                </div>
            </div>

            {{-- Delivery --}}
            @if ($order->delivery_address || $order->delivery_recipient)
                <div class="card p-5 text-sm">
                    <h3 class="font-semibold text-ink">Delivery</h3>
                    <div class="mt-3 space-y-1 text-ink-soft">
                        @if ($order->delivery_recipient)<p class="font-medium text-ink">{{ $order->delivery_recipient }}</p>@endif
                        @if ($order->delivery_phone)<p>{{ $order->delivery_phone }}</p>@endif
                        @if ($order->delivery_address)<p>{{ $order->delivery_address }}</p>@endif
                        @if ($order->delivery_note)<p class="text-ink-muted">“{{ $order->delivery_note }}”</p>@endif
                    </div>
                </div>
            @endif

            {{-- Role actions --}}
            @include('orders._actions', ['role' => $role, 'order' => $order])
        </div>
    </div>

</x-layouts.dashboard>
