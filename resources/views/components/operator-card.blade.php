@props([
    'operator', // App\Models\OperatorProfile
])

@php
    $coverUrl = $operator->getFirstMediaUrl('cover');
    $logoUrl = $operator->getFirstMediaUrl('logo');
    $initials = \Illuminate\Support\Str::of($operator->business_name)->explode(' ')->map(fn ($p) => $p[0])->take(2)->implode('');
@endphp

<a href="{{ route('directory.show', $operator) }}" class="card card-hover group block overflow-hidden">
    {{-- Cover banner — the operator's uploaded image, or the brand gradient when
         none has been set yet. --}}
    <div class="relative h-28 overflow-hidden bg-lyvo-gradient">
        @if ($coverUrl)
            <img src="{{ $coverUrl }}" alt="{{ $operator->business_name }} cover"
                 class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105" />
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-ink/40 to-transparent"></div>
        {{-- Logo --}}
        <div class="absolute -bottom-6 left-5">
            <div class="h-14 w-14 overflow-hidden rounded-2xl bg-lyvo-gradient ring-4 ring-white">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $operator->business_name }} logo" class="h-full w-full object-cover" />
                @else
                    <span class="grid h-full w-full place-items-center text-lg font-bold text-white">{{ $initials }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="px-5 pb-5 pt-8">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <h3 class="flex items-center gap-1.5 truncate font-semibold text-ink">
                    {{ $operator->business_name }}
                    <x-icon name="check-circle" class="h-4 w-4 shrink-0 text-primary-500" />
                </h3>
                <p class="mt-0.5 text-xs text-ink-muted">{{ $operator->category?->name ?? 'Operator' }}</p>
            </div>
            @isset($operator->published_products_count)
                <div class="flex shrink-0 items-center gap-1 rounded-lg bg-primary-50 px-2 py-1 text-xs font-semibold text-primary-700">
                    <x-icon name="box" class="h-3.5 w-3.5" />
                    {{ $operator->published_products_count }}
                </div>
            @endisset
        </div>

        @if ($operator->business_description)
            <p class="mt-3 line-clamp-2 text-sm text-ink-soft/80">{{ $operator->business_description }}</p>
        @endif

        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
            @if ($operator->business_location)
                <span class="flex items-center gap-1 text-xs text-ink-muted">
                    <x-icon name="map-pin" class="h-4 w-4" />
                    {{ \Illuminate\Support\Str::before($operator->business_location, ',') }}
                </span>
            @else
                <span></span>
            @endif
            <span class="badge-verified">
                <x-icon name="shield-check" class="h-3.5 w-3.5" />
                Trust {{ $operator->trust_score }}
            </span>
        </div>
    </div>
</a>
