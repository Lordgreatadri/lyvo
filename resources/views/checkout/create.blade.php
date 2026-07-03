<x-layouts.public :title="'Checkout — '.$product->name">

    @php
        $hero = $product->getFirstMedia('images');
        $currency = $product->currency === 'GHS' ? 'GH₵' : $product->currency;
        $default = $addresses->firstWhere('is_default', true) ?? $addresses->first();
    @endphp

    <section class="bg-lyvo-radial pt-28 pb-16 sm:pt-32">
        <div class="container-lyvo max-w-4xl">
            <a href="{{ route('store.show', $product) }}" class="mb-6 inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-ink">
                <x-icon name="arrow-right" class="h-4 w-4 rotate-180" /> Back to item
            </a>

            <h1 class="font-display text-3xl font-bold text-ink">Secure checkout</h1>
            <p class="mt-1 text-ink-muted">Your payment is held in escrow and only released when you confirm delivery.</p>

            @if (session('error'))
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('checkout.store', $product) }}" class="mt-8 grid gap-6 lg:grid-cols-3" x-data="{ qty: {{ old('quantity', 1) }} }">
                @csrf

                <div class="space-y-6 lg:col-span-2">
                    {{-- Item --}}
                    <div class="card flex items-center gap-4 p-5">
                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-surface-muted">
                            @if ($hero)
                                <img src="{{ $hero->getUrl() }}" alt="" class="h-full w-full object-cover" />
                            @else
                                <div class="grid h-full w-full place-items-center bg-lyvo-gradient-soft text-primary-600"><x-icon name="box" class="h-6 w-6" /></div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-ink">{{ $product->name }}</p>
                            <p class="text-sm text-ink-muted">{{ $product->operator->business_name }}</p>
                        </div>
                        <p class="font-display text-lg font-bold text-primary-700">{{ $currency }} {{ number_format((float) $product->price, 2) }}</p>
                    </div>

                    {{-- Quantity --}}
                    <div class="card p-5">
                        <label class="text-sm font-semibold text-ink">Quantity</label>
                        <div class="mt-3 inline-flex items-center gap-3">
                            <button type="button" @click="qty = Math.max(1, qty - 1)" class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 text-ink-soft">−</button>
                            <input type="number" name="quantity" min="1" max="20" x-model="qty" class="form-input w-20 text-center" />
                            <button type="button" @click="qty = Math.min(20, qty + 1)" class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 text-ink-soft">+</button>
                        </div>
                    </div>

                    {{-- Delivery address --}}
                    <div class="card p-5">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-semibold text-ink">Delivery address</label>
                            <a href="{{ route('customer.addresses.index') }}" class="text-xs font-medium text-primary-600 hover:underline">Manage</a>
                        </div>
                        @if ($addresses->isEmpty())
                            <p class="mt-3 text-sm text-ink-muted">No saved address — the seller will contact you to arrange delivery.</p>
                        @else
                            <div class="mt-3 space-y-2">
                                @foreach ($addresses as $address)
                                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 has-[:checked]:border-primary-400 has-[:checked]:bg-lyvo-gradient-soft">
                                        <input type="radio" name="delivery_address_id" value="{{ $address->id }}" class="mt-1" @checked((int) old('delivery_address_id', optional($default)->id) === $address->id) />
                                        <span class="text-sm">
                                            <span class="font-medium text-ink">{{ $address->label }} · {{ $address->recipient_name }}</span><br>
                                            <span class="text-ink-muted">{{ $address->address_line }}, {{ $address->city }}, {{ $address->region }} · {{ $address->phone }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        <textarea name="note" rows="2" class="form-input mt-3" placeholder="Delivery note (optional)">{{ old('note') }}</textarea>
                    </div>

                    {{-- Payment --}}
                    <div class="card p-5">
                        <label class="text-sm font-semibold text-ink">Mobile money</label>
                        <div class="mt-3 grid gap-2 sm:grid-cols-3">
                            @foreach ($channels as $channel)
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm has-[:checked]:border-primary-400 has-[:checked]:bg-lyvo-gradient-soft">
                                    <input type="radio" name="channel" value="{{ $channel->value }}" @checked(old('channel', 'mtn') === $channel->value) />
                                    <span class="font-medium text-ink">{{ $channel->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                        <input type="tel" name="payer_phone" value="{{ old('payer_phone', auth()->user()->phone) }}" required class="form-input mt-3" placeholder="Mobile money number (e.g. 024 000 0000)" />
                    </div>
                </div>

                {{-- Order summary --}}
                <div class="space-y-4">
                    <div class="card overflow-hidden">
                        <div class="bg-ink p-5 text-white">
                            <p class="text-xs text-slate-400">Total to pay</p>
                            <p class="mt-1 font-display text-3xl font-extrabold">{{ $currency }} <span x-text="(qty * {{ (float) $product->price }}).toFixed(2)">{{ number_format((float) $product->price, 2) }}</span></p>
                            <div class="mt-3 flex items-center gap-2 text-xs text-primary-300"><x-icon name="lock" class="h-4 w-4" /> Held in escrow</div>
                        </div>
                        <div class="p-5">
                            <button type="submit" class="btn-primary w-full"><x-icon name="lock" class="h-5 w-5" /> Pay securely</button>
                            <p class="mt-3 text-xs text-ink-muted">By continuing you agree funds stay in escrow until you confirm delivery.</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

</x-layouts.public>
