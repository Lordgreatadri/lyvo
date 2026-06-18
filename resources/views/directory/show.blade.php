<x-layouts.public :title="$operator['name'] . ' — LYVO'">

    {{-- ===== Cover + header ===== --}}
    <section class="pt-18">
        <div class="relative h-56 bg-gradient-to-br {{ $operator['cover'] }} sm:h-64">
            <div class="absolute inset-0 bg-ink/10"></div>
        </div>

        <div class="container-lyvo">
            <div class="-mt-16 flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-end">
                    <div class="grid h-28 w-28 place-items-center rounded-3xl bg-gradient-to-br {{ $operator['logo_bg'] }} text-3xl font-bold text-white ring-4 ring-white">
                        {{ \Illuminate\Support\Str::of($operator['name'])->explode(' ')->map(fn ($p) => $p[0])->take(2)->implode('') }}
                    </div>
                    <div class="pb-1">
                        <div class="flex items-center gap-2">
                            <h1 class="font-display text-2xl font-bold text-ink sm:text-3xl">{{ $operator['name'] }}</h1>
                            @if ($operator['verified'])
                                <span class="badge-verified"><x-icon name="shield-check" class="h-3.5 w-3.5" /> Verified Operator</span>
                            @endif
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-ink-muted">
                            <span class="flex items-center gap-1"><x-icon name="map-pin" class="h-4 w-4" /> {{ $operator['location'] }}</span>
                            <span class="flex items-center gap-1"><x-icon name="star" class="h-4 w-4 fill-amber-400 text-amber-400" /> {{ number_format($operator['rating'], 1) }} ({{ $operator['reviews'] }} reviews)</span>
                            <span class="badge-info">{{ $operator['category'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="#" class="btn-outline btn-sm"><x-icon name="message" class="h-4 w-4" /> Message</a>
                    <a href="tel:{{ $operator['phone'] }}" class="btn-outline btn-sm"><x-icon name="phone" class="h-4 w-4" /> Call</a>
                    <a href="#" class="btn-primary btn-sm"><x-icon name="wallet" class="h-4 w-4" /> Pay with Escrow</a>
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
                    <p class="mt-3 text-ink-soft/90">{{ $operator['tagline'] }} We pride ourselves on quality, fast response and honest service — every order is backed by LYVO escrow protection.</p>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="flex items-center gap-3 rounded-xl bg-surface-muted p-3 text-sm">
                            <x-icon name="user" class="h-5 w-5 text-primary-600" />
                            <div><p class="text-ink-muted">Owner</p><p class="font-medium text-ink">{{ $operator['owner'] }}</p></div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl bg-surface-muted p-3 text-sm">
                            <x-icon name="globe" class="h-5 w-5 text-primary-600" />
                            <div><p class="text-ink-muted">Operating hours</p><p class="font-medium text-ink">{{ $operator['hours'] }}</p></div>
                        </div>
                    </div>
                </div>

                {{-- Products & services --}}
                <div class="card p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-ink">Products &amp; Services</h2>
                        <span class="text-sm text-ink-muted">{{ count($products) }} items</span>
                    </div>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        @foreach ($products as $product)
                            <div class="group overflow-hidden rounded-2xl border border-slate-100">
                                <div class="relative h-36 bg-gradient-to-br {{ $product['image_bg'] }}">
                                    @if ($product['tag'])
                                        <span class="absolute left-3 top-3 badge bg-white/85 text-ink backdrop-blur">{{ $product['tag'] }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between p-4">
                                    <div>
                                        <p class="font-medium text-ink">{{ $product['name'] }}</p>
                                        <p class="text-sm font-semibold text-primary-600">GH₵ {{ number_format($product['price'], 2) }}</p>
                                    </div>
                                    <button class="btn-primary btn-sm"><x-icon name="wallet" class="h-4 w-4" /> Escrow</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Reviews --}}
                <div class="card p-6">
                    <h2 class="text-lg font-semibold text-ink">Reviews &amp; Ratings</h2>
                    <div class="mt-5 space-y-4">
                        @foreach ($reviews as $review)
                            <div class="rounded-2xl border border-slate-100 p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="grid h-9 w-9 place-items-center rounded-full bg-surface-muted text-xs font-bold text-ink">
                                            {{ \Illuminate\Support\Str::of($review['author'])->explode(' ')->map(fn ($p) => $p[0])->take(2)->implode('') }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-ink">{{ $review['author'] }}</p>
                                            <p class="text-xs text-ink-muted">{{ $review['date'] }}</p>
                                        </div>
                                    </div>
                                    <div class="flex">
                                        @for ($s = 1; $s <= 5; $s++)
                                            <x-icon name="star" class="h-4 w-4 {{ $s <= $review['rating'] ? 'fill-amber-400 text-amber-400' : 'text-slate-200' }}" />
                                        @endfor
                                    </div>
                                </div>
                                <p class="mt-3 text-sm text-ink-soft/90">{{ $review['body'] }}</p>
                            </div>
                        @endforeach
                    </div>
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
                                    stroke-dasharray="326" stroke-dashoffset="{{ 326 - (326 * $operator['trust_score'] / 100) }}" />
                            <defs>
                                <linearGradient id="tg" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0" stop-color="#0B5FA5" /><stop offset="0.5" stop-color="#0F9B8E" /><stop offset="1" stop-color="#0EA86F" />
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="absolute text-center">
                            <p class="font-display text-4xl font-extrabold text-ink">{{ $operator['trust_score'] }}</p>
                            <p class="text-xs text-ink-muted">/ 100</p>
                        </div>
                    </div>
                    <span class="badge-verified mt-3">{{ $operator['trust_level'] }}</span>
                </div>

                {{-- Verification section --}}
                <div class="card p-6">
                    <h3 class="text-sm font-semibold text-ink">Verification</h3>
                    <div class="mt-4 space-y-3">
                        @foreach ([['id-card', 'Identity Verified'], ['id-card', 'Ghana Card Verified'], ['video', 'Video Verified']] as [$icon, $label])
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-2 text-sm text-ink-soft"><x-icon name="{{ $icon }}" class="h-4 w-4 text-primary-600" /> {{ $label }}</span>
                                <x-icon name="check-circle" class="h-5 w-5 text-primary-500" />
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Contact --}}
                <div class="card p-6">
                    <h3 class="text-sm font-semibold text-ink">Contact</h3>
                    <div class="mt-4 grid grid-cols-3 gap-2">
                        <a href="#" class="flex flex-col items-center gap-1.5 rounded-xl bg-surface-muted py-3 text-xs font-medium text-ink-soft hover:bg-slate-100"><x-icon name="message" class="h-5 w-5 text-primary-600" /> Message</a>
                        <a href="tel:{{ $operator['phone'] }}" class="flex flex-col items-center gap-1.5 rounded-xl bg-surface-muted py-3 text-xs font-medium text-ink-soft hover:bg-slate-100"><x-icon name="phone" class="h-5 w-5 text-primary-600" /> Call</a>
                        <a href="#" class="flex flex-col items-center gap-1.5 rounded-xl bg-surface-muted py-3 text-xs font-medium text-ink-soft hover:bg-slate-100"><x-icon name="globe" class="h-5 w-5 text-primary-600" /> WhatsApp</a>
                    </div>
                    <p class="mt-4 rounded-xl bg-primary-50 p-3 text-xs text-primary-700">Sign up to unlock secure transactions, messaging and escrow.</p>
                </div>
            </div>
        </div>
    </section>

</x-layouts.public>
