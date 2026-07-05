<x-layouts.public :title="$product->name.' — LYVO'">

    @php
        $images = $product->getMedia('images');
        $hero = $images->first();
        $heroUrl = $hero ? $hero->getUrl() : null;
        $currency = $product->currency === 'GHS' ? 'GH₵' : $product->currency;
    @endphp

    <section class="bg-lyvo-radial pt-28 pb-12 sm:pt-32">
        <div class="container-lyvo">
            <nav class="mb-6 flex items-center gap-2 text-sm text-ink-muted">
                <a href="{{ route('store.index') }}" class="hover:text-ink">Marketplace</a>
                <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                @if ($product->category)
                    <a href="{{ route('store.index', ['category' => $product->category->slug]) }}" class="hover:text-ink">{{ $product->category->name }}</a>
                @endif
            </nav>

            <div class="grid gap-8 lg:grid-cols-2">
                {{-- Gallery --}}
                <div class="space-y-3">
                    <div class="aspect-square overflow-hidden rounded-2xl bg-surface-muted">
                        @if ($heroUrl)
                            <img src="{{ $heroUrl }}" alt="{{ $product->name }}" class="h-full w-full object-cover" />
                        @else
                            <div class="grid h-full w-full place-items-center bg-lyvo-gradient-soft text-primary-600"><x-icon name="box" class="h-16 w-16" /></div>
                        @endif
                    </div>
                    @if ($images->count() > 1)
                        <div class="grid grid-cols-5 gap-2">
                            @foreach ($images->take(5) as $media)
                                <div class="aspect-square overflow-hidden rounded-xl bg-surface-muted">
                                    <img src="{{ $media->getUrl() }}" alt="" class="h-full w-full object-cover" />
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Details --}}
                <div>
                    @if ($product->category)
                        <span class="eyebrow">{{ $product->category->name }}</span>
                    @endif
                    <h1 class="mt-4 font-display text-3xl font-bold text-ink sm:text-4xl">{{ $product->name }}</h1>

                    <div class="mt-4 flex items-center gap-2">
                        <a href="{{ route('directory.show', $product->operator) }}" class="flex items-center gap-1.5 text-sm font-medium text-ink hover:text-primary-600">
                            {{ $product->operator->business_name }}
                            <x-icon name="check-circle" class="h-4 w-4 text-primary-500" />
                        </a>
                        <span class="badge-verified"><x-icon name="shield-check" class="h-3.5 w-3.5" /> Verified operator</span>
                    </div>

                    <p class="mt-6 font-display text-4xl font-extrabold text-primary-700">{{ $currency }} {{ number_format((float) $product->price, 2) }}</p>

                    @if ($product->isInStock())
                        <p class="mt-2 text-sm font-medium text-emerald-600">In stock</p>
                    @else
                        <p class="mt-2 text-sm font-medium text-rose-600">Sold out</p>
                    @endif

                    @if ($product->description)
                        <div class="mt-6 whitespace-pre-line text-ink-soft/90">{{ $product->description }}</div>
                    @endif

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        @php $viewer = auth()->user(); @endphp
                        @if (! $product->isInStock())
                            <button type="button" disabled class="btn-primary h-12 flex-1 cursor-not-allowed opacity-60">
                                <x-icon name="lock" class="h-5 w-5" /> Sold out
                            </button>
                        @elseif (! $viewer)
                            <a href="{{ route('login') }}" class="btn-primary h-12 flex-1">
                                <x-icon name="lock" class="h-5 w-5" /> Sign in to pay with escrow
                            </a>
                        @elseif ($viewer->isCustomer())
                            <a href="{{ route('checkout.create', $product) }}" class="btn-primary h-12 flex-1">
                                <x-icon name="lock" class="h-5 w-5" /> Pay with LYVO Escrow
                            </a>
                        @else
                            <button type="button" disabled class="btn-primary h-12 flex-1 cursor-not-allowed opacity-60">
                                <x-icon name="lock" class="h-5 w-5" /> Customer account required
                            </button>
                        @endif
                    </div>

                    <div class="mt-6 flex items-center gap-3 rounded-2xl bg-lyvo-gradient-soft p-4 text-sm text-ink-soft">
                        <x-icon name="shield-check" class="h-6 w-6 shrink-0 text-primary-600" />
                        Your payment is held safely in escrow and only released to the seller after you confirm delivery.
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($related->count())
        <section class="section bg-surface-muted">
            <div class="container-lyvo">
                <h2 class="mb-6 font-display text-2xl font-bold text-ink">More like this</h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($related as $item)
                        <x-product-card :product="$item" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

</x-layouts.public>
