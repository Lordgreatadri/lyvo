<x-layouts.public :title="$operator->business_name . ' — LYVO'">

    @php
        $coverUrl = $operator->getFirstMediaUrl('cover');
        $logoUrl = $operator->getFirstMediaUrl('logo');
        $initials = \Illuminate\Support\Str::of($operator->business_name)->explode(' ')->map(fn ($p) => $p[0])->take(2)->implode('');
    @endphp

    {{-- ===== Cover + header ===== --}}
    <section class="pt-18">
        <div class="relative h-56 sm:h-64 {{ $coverUrl ? '' : 'bg-lyvo-gradient' }}">
            @if ($coverUrl)
                <img src="{{ $coverUrl }}" alt="{{ $operator->business_name }} cover" class="h-full w-full object-cover" />
            @endif
            <div class="absolute inset-0 bg-ink/10"></div>
        </div>

        <div class="container-lyvo">
            <div class="-mt-16 flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-end">
                    <div class="h-28 w-28 shrink-0 overflow-hidden rounded-3xl bg-lyvo-gradient ring-4 ring-white">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $operator->business_name }} logo" class="h-full w-full object-cover" />
                        @else
                            <span class="grid h-full w-full place-items-center text-3xl font-bold text-white">{{ $initials }}</span>
                        @endif
                    </div>
                    <div class="pb-1">
                        <div class="flex items-center gap-2">
                            <h1 class="font-display text-2xl font-bold text-ink sm:text-3xl">{{ $operator->business_name }}</h1>
                            <span class="badge-verified"><x-icon name="shield-check" class="h-3.5 w-3.5" /> Verified Operator</span>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-ink-muted">
                            @if ($operator->business_location)
                                <span class="flex items-center gap-1"><x-icon name="map-pin" class="h-4 w-4" /> {{ $operator->business_location }}</span>
                            @endif
                            <span class="flex items-center gap-1"><x-icon name="shield-check" class="h-4 w-4 text-primary-500" /> Trust {{ $operator->trust_score }}</span>
                            @if ($operator->category)
                                <span class="badge-info">{{ $operator->category->name }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('store.index') }}" class="btn-outline btn-sm"><x-icon name="search" class="h-4 w-4" /> Marketplace</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Body ===== --}}
    <section class="section pt-12">
        <div class="container-lyvo grid gap-8 lg:grid-cols-3">

            {{-- Left column --}}
            <div class="space-y-8 lg:col-span-2">
                {{-- About --}}
                <div class="card p-6">
                    <h2 class="text-lg font-semibold text-ink">About</h2>
                    <p class="mt-3 whitespace-pre-line text-ink-soft/90">{{ $operator->business_description ?: 'This verified operator sells on LYVO with full escrow protection — every order is held securely until you confirm delivery.' }}</p>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="flex items-center gap-3 rounded-xl bg-surface-muted p-3 text-sm">
                            <x-icon name="user" class="h-5 w-5 text-primary-600" />
                            <div><p class="text-ink-muted">Owner</p><p class="font-medium text-ink">{{ $operator->owner_full_name ?: $operator->user?->name }}</p></div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl bg-surface-muted p-3 text-sm">
                            <x-icon name="shield-check" class="h-5 w-5 text-primary-600" />
                            <div><p class="text-ink-muted">Status</p><p class="font-medium text-ink">{{ $operator->verification_status->label() }}</p></div>
                        </div>
                    </div>
                </div>

                {{-- Products & services --}}
                <div class="card p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-ink">Products &amp; Services</h2>
                        <span class="text-sm text-ink-muted">{{ $products->count() }} items</span>
                    </div>
                    @if ($products->isEmpty())
                        <p class="mt-5 text-sm text-ink-muted">This operator hasn't listed any products yet.</p>
                    @else
                        <div class="mt-5 grid grid-cols-2 gap-5 sm:grid-cols-3">
                            @foreach ($products as $product)
                                <x-product-card :product="$product" />
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right column --}}
            <div class="space-y-6">
                {{-- Trust score --}}
                <div class="card p-6 text-center">
                    <p class="text-sm font-medium text-ink-muted">Trust Score</p>
                    <div class="relative mx-auto mt-3 grid h-40 w-40 place-items-center">
                        <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="#F5F7FA" stroke-width="10" />
                            <circle cx="60" cy="60" r="52" fill="none" stroke="url(#tg)" stroke-width="10" stroke-linecap="round"
                                    stroke-dasharray="326" stroke-dashoffset="{{ 326 - (326 * (int) $operator->trust_score / 100) }}" />
                            <defs>
                                <linearGradient id="tg" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0" stop-color="#0B5FA5" /><stop offset="0.5" stop-color="#0F9B8E" /><stop offset="1" stop-color="#0EA86F" />
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="absolute text-center">
                            <p class="font-display text-4xl font-extrabold text-ink">{{ $operator->trust_score }}</p>
                            <p class="text-xs text-ink-muted">/ 100</p>
                        </div>
                    </div>
                    <span class="badge-verified mt-3">Verified Operator</span>
                </div>

                {{-- Verification section --}}
                <div class="card p-6">
                    <h3 class="text-sm font-semibold text-ink">Verification</h3>
                    <div class="mt-4 space-y-3">
                        @foreach ([['id-card', 'Identity Verified'], ['id-card', 'Ghana Card Verified'], ['shield-check', 'Video Verified']] as [$icon, $label])
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-2 text-sm text-ink-soft"><x-icon name="{{ $icon }}" class="h-4 w-4 text-primary-600" /> {{ $label }}</span>
                                <x-icon name="check-circle" class="h-5 w-5 text-primary-500" />
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-4 rounded-xl bg-primary-50 p-3 text-xs text-primary-700">Every purchase is protected by LYVO escrow — funds release only after you confirm delivery.</p>
                </div>
            </div>
        </div>
    </section>

</x-layouts.public>
