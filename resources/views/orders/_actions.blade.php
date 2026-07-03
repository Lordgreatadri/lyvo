@php
    use App\Enums\OrderStatus;
@endphp

@if ($role === 'operator')
    @if (in_array($order->status, [OrderStatus::FundsHeld, OrderStatus::Processing], true))
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink">Fulfilment</h3>
            <div class="mt-4 space-y-2">
                @if ($order->status === OrderStatus::FundsHeld)
                    <form method="POST" action="{{ route('operator.orders.processing', $order) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn-primary w-full"><x-icon name="box" class="h-4 w-4" /> Mark as processing</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('operator.orders.delivered', $order) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn-outline w-full"><x-icon name="check-circle" class="h-4 w-4" /> Mark as delivered</button>
                </form>
            </div>
            <p class="mt-3 text-xs text-ink-muted">Funds are released to you after the buyer confirms delivery.</p>
        </div>
    @endif

@elseif ($role === 'customer')
    @if (in_array($order->status, [OrderStatus::FundsHeld, OrderStatus::Processing, OrderStatus::Delivered], true))
        <div class="card p-5" x-data="{ dispute: false }">
            <h3 class="text-sm font-semibold text-ink">Your actions</h3>
            <div class="mt-4 space-y-2">
                @if ($order->status === OrderStatus::Delivered)
                    <form method="POST" action="{{ route('customer.orders.confirm', $order) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn-primary w-full"><x-icon name="check-circle" class="h-4 w-4" /> Confirm delivery &amp; release funds</button>
                    </form>
                @endif
                <button type="button" @click="dispute = ! dispute" class="btn-outline w-full text-rose-600 hover:border-rose-300"><x-icon name="flag" class="h-4 w-4" /> Raise a dispute</button>
            </div>

            <form x-show="dispute" x-cloak method="POST" action="{{ route('customer.orders.dispute', $order) }}" class="mt-4 space-y-2">
                @csrf @method('PATCH')
                <textarea name="reason" rows="3" required class="form-input" placeholder="Tell us what went wrong…"></textarea>
                <button type="submit" class="btn-dark w-full">Submit dispute</button>
            </form>

            <p class="mt-3 text-xs text-ink-muted">Funds are only released to the seller once you confirm delivery.</p>
        </div>
    @endif

@elseif ($role === 'admin')
    @if ($order->status === OrderStatus::Disputed)
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink">Resolve dispute</h3>
            <form method="POST" action="{{ route('admin.orders.release', $order) }}" class="mt-4 space-y-2">
                @csrf @method('PATCH')
                <input type="text" name="note" class="form-input" placeholder="Resolution note (optional)" />
                <button type="submit" class="btn-primary w-full"><x-icon name="wallet" class="h-4 w-4" /> Release funds to seller</button>
            </form>
            <form method="POST" action="{{ route('admin.orders.refund', $order) }}" class="mt-2">
                @csrf @method('PATCH')
                <button type="submit" class="btn-outline w-full text-rose-600 hover:border-rose-300"><x-icon name="flag" class="h-4 w-4" /> Refund the buyer</button>
            </form>
        </div>
    @endif
@endif
