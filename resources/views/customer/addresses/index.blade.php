<x-layouts.dashboard role="customer" title="Delivery Addresses" heading="Delivery Addresses" subheading="Save up to {{ config('lyvo.customer.max_delivery_addresses') }} addresses and choose a default for checkout.">

    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-2xl bg-amber-400 px-4 py-3 text-sm font-medium text-amber-900"><b>{{ session('error') }} </b></div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Saved addresses --}}
        <div class="space-y-4 lg:col-span-2">
            @forelse ($addresses as $address)
                <div class="card p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-ink">{{ $address->label ?: 'Address' }}</p>
                                @if ($address->is_default)
                                    <span class="badge-verified"><x-icon name="check" class="h-3.5 w-3.5" /> Default</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-ink-soft">{{ $address->recipient_name }} · {{ $address->phone }}</p>
                            <p class="mt-1 text-sm text-ink-muted">
                                {{ $address->address_line }} @if ($address->area), {{ $address->area }}@endif @if ($address->city), {{ $address->city }}@endif @if ($address->region), {{ $address->region }}@endif
                            </p>
                            @if ($address->landmark)
                                <p class="text-xs text-ink-muted">Landmark: {{ $address->landmark }}</p>
                            @endif
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-2">
                            @unless ($address->is_default)
                                <form method="POST" action="{{ route('customer.addresses.default', $address) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-xs font-medium text-primary-600 hover:underline">Set default</button>
                                </form>
                            @endunless
                            <form method="POST" action="{{ route('customer.addresses.destroy', $address) }}"
                                  onsubmit="return confirm('Remove this address?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-rose-500 hover:underline">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card p-8 text-center text-sm text-ink-muted">
                    You haven't added any delivery addresses yet.
                </div>
            @endforelse
        </div>

        {{-- Add new --}}
        <div>
            <div class="card p-5">
                <h2 class="font-semibold text-ink">Add an address</h2>
                @if ($maxReached)
                    <p class="mt-3 rounded-xl bg-amber-50 px-3 py-2.5 text-xs text-amber-700">
                        You've reached the maximum of {{ config('lyvo.customer.max_delivery_addresses') }} addresses. Remove one to add another.
                    </p>
                @else
                    <form method="POST" action="{{ route('customer.addresses.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label class="form-label">Label (optional)</label>
                            <input type="text" name="label" value="{{ old('label') }}" class="form-input" placeholder="Home, Office…" />
                        </div>
                        <div>
                            <label class="form-label">Recipient name</label>
                            <input type="text" name="recipient_name" value="{{ old('recipient_name') }}" class="form-input" />
                            <x-input-error :messages="$errors->get('recipient_name')" class="mt-1" />
                        </div>
                        <div>
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" value="{{ old('phone') }}" class="form-input" placeholder="+233 …" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Region</label>
                                <input type="text" name="region" value="{{ old('region') }}" class="form-input" />
                            </div>
                            <div>
                                <label class="form-label">City</label>
                                <input type="text" name="city" value="{{ old('city') }}" class="form-input" />
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Area</label>
                            <input type="text" name="area" value="{{ old('area') }}" class="form-input" />
                        </div>
                        <div>
                            <label class="form-label">Address line</label>
                            <input type="text" name="address_line" value="{{ old('address_line') }}" class="form-input" />
                            <x-input-error :messages="$errors->get('address_line')" class="mt-1" />
                        </div>
                        <div>
                            <label class="form-label">Landmark (optional)</label>
                            <input type="text" name="landmark" value="{{ old('landmark') }}" class="form-input" />
                        </div>
                        <label class="flex items-center gap-2 text-sm text-ink-soft">
                            <input type="checkbox" name="is_default" value="1" class="rounded border-slate-300 text-primary-600 focus:ring-primary-400">
                            Set as default
                        </label>
                        <button type="submit" class="btn-primary w-full">Save address</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

</x-layouts.dashboard>
