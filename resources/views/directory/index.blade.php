<x-layouts.public title="Verified Operators — LYVO">

    {{-- ===== Directory hero ===== --}}
    <section class="bg-lyvo-radial pt-28 pb-12 sm:pt-32">
        <div class="container-lyvo">
            <div class="max-w-2xl">
                <span class="eyebrow"><x-icon name="shield-check" class="h-4 w-4" /> Verified Directory</span>
                <h1 class="mt-5 font-display text-4xl font-bold text-ink sm:text-5xl">Find verified operators you can trust</h1>
                <p class="mt-4 text-ink-soft/80">Only operators who pass Ghana Card, identity and video verification appear here.</p>
            </div>

            {{-- Search + filters --}}
            <div class="mt-8 card p-3 sm:p-4">
                <div class="flex flex-col gap-3 lg:flex-row">
                    <div class="relative flex-1">
                        <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-muted" />
                        <input type="text" placeholder="Search businesses, products or services…" class="form-input h-12 pl-12" />
                    </div>
                    <div class="relative">
                        <x-icon name="map-pin" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-muted" />
                        <select class="form-select h-12 pl-12 lg:w-48">
                            <option>All locations</option>
                            <option>Greater Accra</option>
                            <option>Ashanti</option>
                            <option>Western</option>
                            <option>Central</option>
                        </select>
                    </div>
                    <button class="btn-primary h-12">Search</button>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Category chips ===== --}}
    <section class="border-y border-slate-100 bg-white">
        <div class="container-lyvo flex gap-2 overflow-x-auto py-4">
            <a href="{{ route('directory.index') }}"
               class="badge shrink-0 {{ empty($activeCategory) ? 'bg-ink text-white' : 'bg-surface-muted text-ink-soft hover:bg-slate-100' }}">All</a>
            @foreach ($categories as $category)
                <a href="{{ route('directory.index', ['category' => $category['slug']]) }}"
                   class="badge shrink-0 {{ ($activeCategory ?? null) === $category['slug'] ? 'bg-ink text-white' : 'bg-surface-muted text-ink-soft hover:bg-slate-100' }}">
                    <x-icon name="{{ $category['icon'] }}" class="h-3.5 w-3.5" />
                    {{ $category['name'] }}
                </a>
            @endforeach
        </div>
    </section>

    {{-- ===== Results ===== --}}
    <section class="section bg-surface-muted">
        <div class="container-lyvo">
            <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-ink-muted"><span class="font-semibold text-ink">{{ count($operators) }}</span> verified operators</p>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-ink-muted">Sort:</span>
                    <select class="form-select py-2 text-sm">
                        <option>Top rated</option>
                        <option>Trust score</option>
                        <option>Newest</option>
                    </select>
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($operators as $operator)
                    <x-operator-card :operator="$operator" />
                @endforeach
            </div>
        </div>
    </section>

</x-layouts.public>
