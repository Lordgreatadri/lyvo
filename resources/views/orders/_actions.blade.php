@php
    use App\Enums\OrderStatus;
    use App\Enums\PaymentStatus;
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
    @if ($order->status === OrderStatus::PendingPayment && $order->payment)
        @php $pay = $order->payment; @endphp

        @if ($pay->status === PaymentStatus::AwaitingOtp)
            <div class="card border-primary-200 p-5">
                <div class="flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-primary-50 text-primary-600"><x-icon name="lock" class="h-4 w-4" /></span>
                    <h3 class="text-sm font-semibold text-ink">Verify your mobile money number</h3>
                </div>
                <p class="mt-3 text-sm text-ink-soft">
                    We sent a one-time PIN by SMS to <span class="font-medium text-ink">{{ $pay->payer }}</span>.
                    Enter it below to authorise the payment. Your funds stay protected by LYVO escrow.
                </p>
                <form method="POST" action="{{ route('customer.orders.otp', $order) }}" class="mt-4 space-y-2">
                    @csrf @method('PATCH')
                    <label class="form-label" for="otp">One-time PIN (OTP)</label>
                    <input id="otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code"
                           maxlength="8" required class="form-input text-center text-lg tracking-[0.4em]"
                           placeholder="••••••" />
                    <x-input-error :messages="$errors->get('otp')" class="mt-1" />
                    <button type="submit" class="btn-primary w-full"><x-icon name="check-circle" class="h-4 w-4" /> Verify &amp; pay</button>
                </form>
                <p class="mt-3 text-xs text-ink-muted">Didn’t get the code? Check your SMS, then re-enter it here.</p>
            </div>
        @elseif ($pay->status === PaymentStatus::AwaitingApproval || $pay->status === PaymentStatus::Processing)
            <div class="card p-5">
                <div class="flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-amber-50 text-amber-600"><x-icon name="shield" class="h-4 w-4" /></span>
                    <h3 class="text-sm font-semibold text-ink">Approve on your phone</h3>
                </div>
                <p class="mt-3 text-sm text-ink-soft">A payment prompt has been sent to {{ $pay->payer }}. Approve it to move your funds into escrow. This page updates once payment is confirmed.</p>
            </div>
        @elseif ($pay->status === PaymentStatus::Failed)
            <div class="card border-rose-200 p-5">
                <h3 class="text-sm font-semibold text-rose-700">Payment could not be completed</h3>
                <p class="mt-2 text-sm text-ink-soft">{{ $pay->failure_reason ?: 'The payment was declined. Please try checking out again.' }}</p>
            </div>
        @endif
    @endif

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

    @if ($order->status === OrderStatus::Released)
        @php $payout = $order->latestPayout(); @endphp

        @if ($payout && $payout->status->value !== 'failed')
            {{-- A payout already exists for this order --}}
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-ink">Operator payout</h3>
                <div class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-ink-muted">Status</span>
                        <span class="badge bg-{{ $payout->status->color() }}-50 text-{{ $payout->status->color() }}-700">{{ $payout->status->label() }}</span>
                    </div>
                    <div class="flex justify-between"><span class="text-ink-muted">Amount</span><span class="font-medium text-ink">GH₵ {{ number_format((float) $payout->amount, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-ink-muted">To</span><span class="font-medium text-ink">{{ $payout->recipient }}</span></div>
                    @if ($payout->recipient_name)
                        <div class="flex justify-between"><span class="text-ink-muted">Name</span><span class="font-medium text-ink">{{ $payout->recipient_name }}</span></div>
                    @endif
                </div>
                @if ($payout->status->isOpen())
                    <form method="POST" action="{{ route('admin.payouts.status', $payout) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="btn-outline btn-sm w-full"><x-icon name="refresh" class="h-4 w-4" /> Refresh payout status</button>
                    </form>
                @endif
            </div>
        @else
            {{-- No successful payout yet — release funds to the operator --}}
            <div class="card p-5" x-data="payoutForm('{{ route('admin.payouts.validate') }}', @js($order->operator?->user?->phone ?? ''))">
                <h3 class="text-sm font-semibold text-ink">Pay operator</h3>
                <p class="mt-1 text-xs text-ink-muted">Funds are released from escrow — disburse them to the seller's mobile money.</p>

                <form method="POST" action="{{ route('admin.payouts.store') }}" class="mt-4 space-y-3" @submit="submitting = true">
                    @csrf
                    <input type="hidden" name="order_id" value="{{ $order->id }}" />
                    <input type="hidden" name="amount" value="{{ number_format((float) $order->total, 2, '.', '') }}" />
                    <input type="hidden" name="recipient_name" :value="name" />

                    <div>
                        <label class="form-label">Channel</label>
                        <select name="channel" x-model="channel" class="form-select">
                            @foreach (\App\Enums\PayoutChannel::mobileMoneyCases() as $channel)
                                <option value="{{ $channel->value }}">{{ $channel->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Momo number</label>
                        <input type="text" name="receiver" x-model="receiver" class="form-input" placeholder="0543645688" />
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="validate" class="btn-outline btn-sm" :disabled="validating || ! receiver">
                            <x-icon name="user" class="h-4 w-4" />
                            <span x-text="validating ? 'Checking…' : 'Validate name'"></span>
                        </button>
                        <template x-if="name"><span class="badge badge-verified" x-text="name"></span></template>
                        <template x-if="error"><span class="text-xs font-medium text-rose-600" x-text="error"></span></template>
                    </div>

                    <button type="submit" class="btn-primary w-full" :disabled="submitting">
                        <x-icon name="wallet" class="h-4 w-4" /> Pay GH₵ {{ number_format((float) $order->total, 2) }} to operator
                    </button>
                </form>
            </div>

            @push('scripts')
                <script>
                    function payoutForm(validateUrl, receiver) {
                        return {
                            validateUrl,
                            channel: 'mtn',
                            receiver: receiver || '',
                            name: '',
                            error: '',
                            validating: false,
                            submitting: false,
                            async validate() {
                                this.error = '';
                                this.name = '';
                                this.validating = true;
                                try {
                                    const res = await fetch(this.validateUrl, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                        },
                                        body: JSON.stringify({ channel: this.channel, receiver: this.receiver }),
                                    });
                                    const data = await res.json();
                                    if (res.ok && data.ok) {
                                        this.name = data.name || '';
                                    } else {
                                        this.error = data.message || 'Could not validate the recipient.';
                                    }
                                } catch (e) {
                                    this.error = 'Network error — please try again.';
                                } finally {
                                    this.validating = false;
                                }
                            },
                        };
                    }
                </script>
            @endpush
        @endif
    @endif
@endif
