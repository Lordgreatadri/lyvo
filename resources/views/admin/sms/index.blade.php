<x-layouts.dashboard role="admin" title="SMS Gateway" heading="SMS Gateway" subheading="Monitor credit, configure the provider and review every message sent from LYVO.">

    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('status') }}</div>
    @endif

    @if ($belowThreshold)
        <div class="mb-4 flex items-start gap-3 rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <x-icon name="flag" class="mt-0.5 h-5 w-5 shrink-0" />
            <span>
                <span class="font-semibold">Low SMS credit.</span>
                Balance ({{ number_format((float) $settings->cached_balance) }}) is below your alert threshold
                ({{ number_format($settings->low_credit_threshold) }}). Top up the account to avoid delivery failures.
            </span>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Balance --}}
        <div class="card p-6">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Credit balance</p>
            <p class="mt-2 text-3xl font-bold text-ink">{{ number_format($balance['balance']) }}</p>
            <p class="mt-1 text-xs text-ink-muted">
                {{ $balance['cached'] ? 'Cached' : 'Live' }}
                @if ($balance['checked_at'])
                    · checked {{ \Illuminate\Support\Carbon::parse($balance['checked_at'])->diffForHumans() }}
                @endif
            </p>
            <form method="POST" action="{{ route('admin.sms.balance') }}" class="mt-4">
                @csrf
                <button type="submit" class="btn-outline btn-sm">Refresh balance</button>
            </form>
        </div>

        {{-- Status breakdown --}}
        <div class="card p-6 lg:col-span-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Messages by status</p>
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

        {{-- Settings --}}
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-ink">Configuration</h2>
            <p class="text-xs text-ink-muted">Gateway, default sender ID and the low-credit alert threshold.</p>

            <form method="POST" action="{{ route('admin.sms.settings') }}" class="mt-4 space-y-4">
                @csrf @method('PUT')

                <div>
                    <label class="form-label" for="provider">Provider</label>
                    <select id="provider" name="provider" class="form-select">
                        @foreach ($providers as $value => $label)
                            <option value="{{ $value }}" @selected(old('provider', $settings->provider) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('provider')" class="mt-1" />
                </div>

                <div>
                    <label class="form-label" for="sender_id">Default sender ID</label>
                    <input id="sender_id" name="sender_id" type="text" maxlength="11" value="{{ old('sender_id', $settings->sender_id) }}" class="form-input" placeholder="LYVO" />
                    <p class="mt-1 text-xs text-ink-muted">Max 11 characters. Must be approved by the provider.</p>
                    <x-input-error :messages="$errors->get('sender_id')" class="mt-1" />
                </div>

                <div>
                    <label class="form-label" for="low_credit_threshold">Low-credit alert threshold</label>
                    <input id="low_credit_threshold" name="low_credit_threshold" type="number" min="0" value="{{ old('low_credit_threshold', $settings->low_credit_threshold) }}" class="form-input" />
                    <p class="mt-1 text-xs text-ink-muted">Admins are alerted when the balance drops below this figure.</p>
                    <x-input-error :messages="$errors->get('low_credit_threshold')" class="mt-1" />
                </div>

                <button type="submit" class="btn-primary btn-sm">Save settings</button>
            </form>
        </div>

        {{-- Sender IDs + test send --}}
        <div class="space-y-6">
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-ink">Sender IDs</h2>
                <p class="text-xs text-ink-muted">Registered with the active provider.</p>
                <div class="mt-3 space-y-2">
                    @forelse ($senderIds as $sender)
                        <div class="flex items-center justify-between rounded-xl border border-slate-100 p-3 text-sm">
                            <span class="font-medium text-ink">{{ $sender['senderid'] }}</span>
                            <span class="flex items-center gap-2">
                                <span class="badge {{ strtolower($sender['approval']) === 'approved' ? 'badge-verified' : 'badge-info' }}">{{ $sender['approval'] }}</span>
                                @if ($sender['whitelisted'])
                                    <span class="badge badge-info">Whitelisted</span>
                                @endif
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted">No sender IDs returned by the provider.</p>
                    @endforelse
                </div>
            </div>

            <div class="card p-6">
                <h2 class="text-lg font-semibold text-ink">Send a test message</h2>
                <form method="POST" action="{{ route('admin.sms.test') }}" class="mt-3 space-y-3">
                    @csrf
                    <div>
                        <label class="form-label" for="recipient">Recipient</label>
                        <input id="recipient" name="recipient" type="text" value="{{ old('recipient') }}" class="form-input" placeholder="0201234567" />
                        <x-input-error :messages="$errors->get('recipient')" class="mt-1" />
                    </div>
                    <div>
                        <label class="form-label" for="message">Message</label>
                        <textarea id="message" name="message" rows="2" class="form-textarea" placeholder="Test message from LYVO">{{ old('message') }}</textarea>
                        <x-input-error :messages="$errors->get('message')" class="mt-1" />
                    </div>
                    <button type="submit" class="btn-outline btn-sm">Send test</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Message log --}}
    <div class="card mt-6 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-ink">Message log</h2>
            <form method="GET" action="{{ route('admin.sms.index') }}">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($statusFilter === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase tracking-wide text-ink-muted">
                    <tr class="border-b border-slate-100">
                        <th class="py-2 pr-4">Recipient</th>
                        <th class="py-2 pr-4">Context</th>
                        <th class="py-2 pr-4">Status</th>
                        <th class="py-2 pr-4">Segments</th>
                        <th class="py-2 pr-4">Provider</th>
                        <th class="py-2 pr-4">Sent</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($messages as $message)
                        <tr class="border-b border-slate-50">
                            <td class="py-2 pr-4 font-medium text-ink">{{ $message->recipient }}</td>
                            <td class="py-2 pr-4 text-ink-muted">{{ $message->context }}</td>
                            <td class="py-2 pr-4"><span class="badge bg-{{ $message->status->color() }}-50 text-{{ $message->status->color() }}-700">{{ $message->status->label() }}</span></td>
                            <td class="py-2 pr-4 text-ink-muted">{{ $message->segments }}</td>
                            <td class="py-2 pr-4 text-ink-muted">{{ $message->provider }}</td>
                            <td class="py-2 pr-4 text-ink-muted">{{ $message->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-ink-muted">No messages yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $messages->links() }}</div>
    </div>

</x-layouts.dashboard>
