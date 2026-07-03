<x-layouts.public title="Marketplace — LYVO">

    {{-- ===== Store hero ===== --}}
    <section class="bg-lyvo-radial pt-28 pb-10 sm:pt-32">
        <div class="container-lyvo">
            <div class="max-w-2xl">
                <span class="eyebrow"><x-icon name="sparkles" class="h-4 w-4" /> LYVO Marketplace</span>
                <h1 class="mt-5 font-display text-4xl font-bold text-ink sm:text-5xl">Shop items from verified operators</h1>
                <p class="mt-4 text-ink-soft/80">Every seller is Ghana Card &amp; identity verified. Pay securely with LYVO Escrow.</p>
            </div>
        </div>
    </section>

    {{-- ===== Category chips ===== --}}
    <section class="border-y border-slate-100 bg-white">
        <div class="container-lyvo flex gap-2 overflow-x-auto py-4">
            <a href="{{ route('store.index') }}"
               class="badge shrink-0 {{ empty($activeCategory) ? 'bg-ink text-white' : 'bg-surface-muted text-ink-soft hover:bg-slate-100' }}">All items</a>
            @foreach ($categories as $category)
                <a href="{{ route('store.index', ['category' => $category->slug]) }}"
                   class="badge shrink-0 {{ ($activeCategory ?? null) === $category->slug ? 'bg-ink text-white' : 'bg-surface-muted text-ink-soft hover:bg-slate-100' }}">
                    @if ($category->icon)<x-icon name="{{ $category->icon }}" class="h-3.5 w-3.5" />@endif
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
    </section>

    {{-- ===== Grid ===== --}}
    <section class="section bg-surface-muted">
        <div class="container-lyvo">
            <p class="mb-8 text-sm text-ink-muted"><span class="font-semibold text-ink">{{ $products->total() }}</span> items available</p>

            @if ($products->count())
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $products->links() }}
                </div>
            @else
                <div class="card grid place-items-center gap-3 p-16 text-center">
                    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-lyvo-gradient-soft text-primary-600"><x-icon name="box" class="h-7 w-7" /></span>
                    <p class="font-semibold text-ink">No items here yet</p>
                    <p class="text-sm text-ink-muted">Check back soon — verified operators are adding items every day.</p>
                </div>
            @endif
        </div>
    </section>

</x-layouts.public>
