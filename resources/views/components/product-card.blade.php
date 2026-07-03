@props([
    'product',
])

@php
    /** @var \App\Models\Product $product */
    $image = $product->getFirstMediaUrl('images', 'thumb') ?: $product->getFirstMediaUrl('images');
@endphp

<a href="{{ route('store.show', $product) }}" class="card card-hover group block overflow-hidden">
    <div class="relative aspect-square overflow-hidden bg-surface-muted">
        @if ($image)
            <img src="{{ $image }}" alt="{{ $product->name }}"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
        @else
            <div class="grid h-full w-full place-items-center bg-lyvo-gradient-soft text-primary-600">
                <x-icon name="box" class="h-10 w-10" />
            </div>
        @endif

        @if ($product->isBoosted())
            <span class="badge absolute left-3 top-3 bg-white/90 text-amber-700 backdrop-blur">
                <x-icon name="sparkles" class="h-3.5 w-3.5" /> Featured
            </span>
        @endif

        @if (! $product->isInStock())
            <span class="badge absolute right-3 top-3 bg-rose-50 text-rose-700">Sold out</span>
        @endif
    </div>

    <div class="p-4">
        <h3 class="truncate font-semibold text-ink">{{ $product->name }}</h3>
        <p class="mt-0.5 flex items-center gap-1 text-xs text-ink-muted">
            @if ($product->relationLoaded('operator') && $product->operator)
                {{ $product->operator->business_name }}
                <x-icon name="check-circle" class="h-3.5 w-3.5 shrink-0 text-primary-500" />
            @endif
        </p>
        <p class="mt-3 font-display text-lg font-bold text-primary-700">
            {{ $product->currency === 'GHS' ? 'GH₵' : $product->currency }} {{ number_format((float) $product->price, 2) }}
        </p>
    </div>
</a>
