<x-layouts.dashboard role="customer" title="Payment Methods" heading="Payment Methods" subheading="Save how you'd like to pay. Only non-sensitive details are stored.">

    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('status') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Saved methods --}}
        <div class="space-y-4 lg:col-span-2">
            @forelse ($methods as $method)
                <div class="card flex items-center justify-between p-5">
                    <div class="flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-lyvo-gradient-soft text-primary-600"><x-icon name="lock" class="h-5 w-5" /></span>
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-ink">{{ $method->provider }}</p>
                                @if ($method->is_default)
                                    <span class="badge-verified"><x-icon name="check" class="h-3.5 w-3.5" /> Default</span>
                                @endif
                            </div>
                            <p class="text-sm text-ink-muted">{{ $method->type->label() }} · {{ $method->account_reference }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        @unless ($method->is_default)
                            <form method="POST" action="{{ route('customer.payment-methods.default', $method) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs font-medium text-primary-600 hover:underline">Set default</button>
                            </form>
                        @endunless
                        <form method="POST" action="{{ route('customer.payment-methods.destroy', $method) }}"
                              onsubmit="return confirm('Remove this payment method?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs font-medium text-rose-500 hover:underline">Remove</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="card p-8 text-center text-sm text-ink-muted">No payment methods saved yet.</div>
            @endforelse
        </div>

        {{-- Add new --}}
        <div>
            <div class="card p-5">
                <h2 class="font-semibold text-ink">Add a method</h2>
                <form method="POST" action="{{ route('customer.payment-methods.store') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            @foreach (\App\Enums\PaymentMethodType::cases() as $type)
                                <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('type')" class="mt-1" />
                    </div>
                    <div>
                        <label class="form-label">Provider</label>
                        <input type="text" name="provider" value="{{ old('provider') }}" class="form-input" placeholder="MTN MoMo, Visa…" />
                        <x-input-error :messages="$errors->get('provider')" class="mt-1" />
                    </div>
                    <div>
                        <label class="form-label">Account name</label>
                        <input type="text" name="account_name" value="{{ old('account_name') }}" class="form-input" />
                        <x-input-error :messages="$errors->get('account_name')" class="mt-1" />
                    </div>
                    <div>
                        <label class="form-label">Account reference</label>
                        <input type="text" name="account_reference" value="{{ old('account_reference') }}" class="form-input" placeholder="****1234" />
                        <x-input-error :messages="$errors->get('account_reference')" class="mt-1" />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-ink-soft">
                        <input type="checkbox" name="is_default" value="1" class="rounded border-slate-300 text-primary-600 focus:ring-primary-400">
                        Set as default
                    </label>
                    <button type="submit" class="btn-primary w-full">Save method</button>
                </form>
            </div>
        </div>
    </div>

</x-layouts.dashboard>
