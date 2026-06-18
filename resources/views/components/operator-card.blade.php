@props([
    'operator' => [],
])

<a href="{{ route('directory.show', $operator['uuid']) }}" class="card card-hover group block overflow-hidden">
    {{-- Cover banner — placeholder business image, replaced when the operator
         uploads their own cover from the dashboard. The category gradient sits
         on top as a tint so the name tag stays readable. --}}
    <div class="relative h-28 overflow-hidden bg-gradient-to-br {{ $operator['cover'] }}">
        <img src="{{ asset($operator['banner'] ?? 'assets/images/banners/images.jpg') }}"
             alt="{{ $operator['name'] }} cover"
             class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105" />
        <div class="absolute inset-0 bg-gradient-to-br {{ $operator['cover'] }} opacity-60 mix-blend-multiply"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-ink/40 to-transparent"></div>
        @if (!empty($operator['tags']))
            <div class="absolute left-3 top-3 flex gap-1.5">
                @foreach ($operator['tags'] as $tag)
                    <span class="badge bg-white/85 text-ink backdrop-blur">
                        @if ($tag === 'Trending') <x-icon name="trending" class="h-3.5 w-3.5" /> @endif
                        {{ $tag }}
                    </span>
                @endforeach
            </div>
        @endif
        {{-- Logo --}}
        <div class="absolute -bottom-6 left-5">
            <div class="grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br {{ $operator['logo_bg'] }} text-lg font-bold text-white ring-4 ring-white">
                {{ \Illuminate\Support\Str::of($operator['name'])->explode(' ')->map(fn ($p) => $p[0])->take(2)->implode('') }}
            </div>
        </div>
    </div>

    <div class="px-5 pb-5 pt-8">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <h3 class="flex items-center gap-1.5 truncate font-semibold text-ink">
                    {{ $operator['name'] }}
                    @if ($operator['verified'])
                        <x-icon name="check-circle" class="h-4 w-4 shrink-0 text-primary-500" />
                    @endif
                </h3>
                <p class="mt-0.5 text-xs text-ink-muted">{{ $operator['category'] }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-1 rounded-lg bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">
                <x-icon name="star" class="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                {{ number_format($operator['rating'], 1) }}
            </div>
        </div>

        <p class="mt-3 line-clamp-2 text-sm text-ink-soft/80">{{ $operator['tagline'] }}</p>

        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
            <span class="flex items-center gap-1 text-xs text-ink-muted">
                <x-icon name="map-pin" class="h-4 w-4" />
                {{ \Illuminate\Support\Str::before($operator['location'], ',') }}
            </span>
            <span class="badge-verified">
                <x-icon name="shield-check" class="h-3.5 w-3.5" />
                Trust {{ $operator['trust_score'] }}
            </span>
        </div>
    </div>
</a>
