<x-layouts.dashboard role="admin" title="Payouts" heading="Payouts" subheading="Validate recipients and release escrow funds to operators via Moolre transfers.">

    @if (session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ session('error') }}</div>
    @endif

    {{-- Summary --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card overflow-hidden">
            <div class="bg-ink p-5 text-white">
                <p class="text-xs text-slate-400">Total disbursed</p>
                <p class="mt-1 font-display text-3xl font-extrabold">GH₵ {{ number_format($settledTotal, 2) }}</p>
                <div class="mt-3 flex items-center gap-2 text-xs text-primary-300">
                    <x-icon name="wallet" class="h-4 w-4" /> Paid to operators
                </div>
            </div>
        </div>

        <div class="card p-6 lg:col-span-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Payouts by status</p>
            <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach ($statuses as $status)
                    <div class="rounded-xl border border-slate-100 p-3">
                        <p class="text-lg font-semibold text-ink">{{ number_format($counts[$status->value] ?? 0) }}</p>
                        <span class="badge bg-{{ $status->color() }}-50 text-{{ $status->color() }}-700">{{ $status->label() }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">

        {{-- Awaiting payout queue --}}
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-ink">Awaiting payout</h2>
                <span class="badge badge-info">{{ $awaiting->count() }}</span>
            </div>
            <p class="text-xs text-ink-muted">Released escrow orders that still owe the operator their funds.</p>

            <div class="mt-4 space-y-3">
                @forelse ($awaiting as $order)
                    <div class="rounded-2xl border border-slate-100 p-4"
                         x-data="payoutForm('{{ route('admin.payouts.validate') }}', @js($order->operator?->user?->phone ?? ''))">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-ink">{{ $order->operator?->business_name ?? '—' }}</p>
                                <p class="text-xs text-ink-muted">Order {{ $order->order_number }} · {{ $order->operator?->user?->name }}</p>
                            </div>
                            <p class="shrink-0 font-display text-lg font-bold text-primary-700">GH₵ {{ number_format((float) $order->total, 2) }}</p>
                        </div>

                        <form method="POST" action="{{ route('admin.payouts.store') }}" class="mt-4 space-y-3" @submit="submitting = true">
                            @csrf
                            <input type="hidden" name="order_id" value="{{ $order->id }}" />
                            <input type="hidden" name="amount" value="{{ number_format((float) $order->total, 2, '.', '') }}" />
                            <input type="hidden" name="recipient_name" :value="name" />

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="form-label">Channel</label>
                                    <select name="channel" x-model="channel" class="form-select">
                                        @foreach ($channels as $channel)
                                            <option value="{{ $channel->value }}">{{ $channel->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Momo number</label>
                                    <input type="text" name="receiver" x-model="receiver" class="form-input" placeholder="0543645688" />
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" @click="validate" class="btn-outline btn-sm" :disabled="validating || ! receiver">
                                    <x-icon name="user" class="h-4 w-4" />
                                    <span x-text="validating ? 'Checking…' : 'Validate name'"></span>
                                </button>
                                <template x-if="name">
                                    <span class="badge badge-verified" x-text="name"></span>
                                </template>
                                <template x-if="error">
                                    <span class="text-xs font-medium text-rose-600" x-text="error"></span>
                                </template>
                            </div>

                            <button type="submit" class="btn-primary w-full" :disabled="submitting">
                                <x-icon name="wallet" class="h-4 w-4" /> Pay operator GH₵ {{ number_format((float) $order->total, 2) }}
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 p-6 text-center text-sm text-ink-muted">No operators are awaiting a payout right now.</p>
                @endforelse
            </div>
        </div>

        {{-- Manual payout --}}
        <div class="card h-fit p-6" x-data="payoutForm('{{ route('admin.payouts.validate') }}', '')">
            <h2 class="text-lg font-semibold text-ink">Manual payout</h2>
            <p class="text-xs text-ink-muted">Send funds to any mobile-money wallet. Always validate the name first.</p>

            <form method="POST" action="{{ route('admin.payouts.store') }}" class="mt-4 space-y-4" @submit="submitting = true">
                @csrf
                <input type="hidden" name="recipient_name" :value="name" />

                <div>
                    <label class="form-label" for="m_channel">Channel</label>
                    <select id="m_channel" name="channel" x-model="channel" class="form-select">
                        @foreach ($channels as $channel)
                            <option value="{{ $channel->value }}">{{ $channel->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('channel')" class="mt-1" />
                </div>

                <div>
                    <label class="form-label" for="m_receiver">Momo number</label>
                    <input id="m_receiver" name="receiver" type="text" x-model="receiver" class="form-input" placeholder="0543645688" value="{{ old('receiver') }}" />
                    <x-input-error :messages="$errors->get('receiver')" class="mt-1" />
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="validate" class="btn-outline btn-sm" :disabled="validating || ! receiver">
                        <x-icon name="user" class="h-4 w-4" />
                        <span x-text="validating ? 'Checking…' : 'Validate name'"></span>
                    </button>
                    <template x-if="name">
                        <span class="badge badge-verified" x-text="name"></span>
                    </template>
                    <template x-if="error">
                        <span class="text-xs font-medium text-rose-600" x-text="error"></span>
                    </template>
                </div>

                <div>
                    <label class="form-label" for="m_amount">Amount (GH₵)</label>
                    <input id="m_amount" name="amount" type="number" step="0.01" min="0.1" class="form-input" placeholder="0.00" value="{{ old('amount') }}" />
                    <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                </div>

                <div>
                    <label class="form-label" for="m_reference">Reference (optional)</label>
                    <input id="m_reference" name="reference" type="text" maxlength="120" class="form-input" placeholder="Payout note" value="{{ old('reference') }}" />
                </div>

                <button type="submit" class="btn-primary w-full" :disabled="submitting">
                    <x-icon name="wallet" class="h-4 w-4" /> Send payout
                </button>
            </form>
        </div>
    </div>

    {{-- Payout log --}}
    <div class="card mt-6 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-ink">Payout log</h2>
            <form method="GET" action="{{ route('admin.payouts.index') }}">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($statusFilter === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-ink-muted">
                        <th class="py-2 pr-4">Recipient</th>
                        <th class="py-2 pr-4">Channel</th>
                        <th class="py-2 pr-4">Amount</th>
                        <th class="py-2 pr-4">Status</th>
                        <th class="py-2 pr-4">Reference</th>
                        <th class="py-2 pr-4">When</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($payouts as $payout)
                        <tr>
                            <td class="py-3 pr-4">
                                <p class="font-medium text-ink">{{ $payout->recipient_name ?: $payout->recipientUser?->name ?: '—' }}</p>
                                <p class="text-xs text-ink-muted">{{ $payout->recipient }}</p>
                            </td>
                            <td class="py-3 pr-4 text-ink-soft">{{ $payout->channel->label() }}</td>
                            <td class="py-3 pr-4 font-semibold text-ink">GH₵ {{ number_format((float) $payout->amount, 2) }}</td>
                            <td class="py-3 pr-4">
                                <span class="badge bg-{{ $payout->status->color() }}-50 text-{{ $payout->status->color() }}-700">{{ $payout->status->label() }}</span>
                            </td>
                            <td class="py-3 pr-4 text-ink-soft">{{ $payout->reference ?: '—' }}</td>
                            <td class="py-3 pr-4 text-ink-muted">{{ $payout->created_at->diffForHumans() }}</td>
                            <td class="py-3 text-right">
                                @if ($payout->status->isOpen())
                                    <form method="POST" action="{{ route('admin.payouts.status', $payout) }}">
                                        @csrf
                                        <button type="submit" class="btn-outline btn-sm" title="Refresh status">
                                            <x-icon name="refresh" class="h-4 w-4" />
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-sm text-ink-muted">No payouts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $payouts->links() }}</div>
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

</x-layouts.dashboard>
