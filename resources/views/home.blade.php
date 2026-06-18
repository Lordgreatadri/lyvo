<x-layouts.public title="LYVO — The Trust Layer for Digital Commerce">

    {{-- ============================== HERO ============================== --}}
    <section class="relative overflow-hidden bg-lyvo-radial pt-28 sm:pt-32">
        <div class="container-lyvo grid items-center gap-12 pb-20 lg:grid-cols-2 lg:pb-28">
            {{-- Copy --}}
            <div class="max-w-xl">
                <span class="eyebrow">
                    <x-icon name="shield-check" class="h-4 w-4" />
                    Verified · Escrow-Protected · Trusted
                </span>

                <h1 class="mt-6 font-display text-4xl font-extrabold leading-[1.1] tracking-tight text-ink sm:text-5xl lg:text-6xl">
                    Trade With <span class="text-gradient">Confidence</span>
                </h1>

                <p class="mt-5 text-lg leading-relaxed text-ink-soft/80">
                    Discover verified businesses, protected transactions, and trusted operators across Ghana —
                    secured by Ghana Card verification and LYVO escrow.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="{{ route('register') }}" class="btn-primary">
                        Get Started
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                    <a href="{{ route('directory.index') }}" class="btn-outline">
                        <x-icon name="search" class="h-4 w-4" />
                        Browse Operators
                    </a>
                    <a href="{{ route('guest.enter') }}" class="btn-ghost">Continue as Guest</a>
                </div>

                {{-- Trust stats --}}
                <div class="mt-12 grid max-w-md grid-cols-3 gap-6">
                    <div>
                        <p class="font-display text-2xl font-bold text-ink">1,162+</p>
                        <p class="text-xs text-ink-muted">Verified Operators</p>
                    </div>
                    <div>
                        <p class="font-display text-2xl font-bold text-ink">GH₵ 4.8M</p>
                        <p class="text-xs text-ink-muted">Protected in Escrow</p>
                    </div>
                    <div>
                        <p class="font-display text-2xl font-bold text-ink">99.2%</p>
                        <p class="text-xs text-ink-muted">Dispute-free</p>
                    </div>
                </div>
            </div>

            {{-- Hero visual --}}
            <div class="relative">
                <div class="relative mx-auto max-w-md">
                    {{-- Trust network glow --}}
                    <div class="absolute inset-0 -z-10 animate-float">
                        <div class="mx-auto h-72 w-72 rounded-full bg-lyvo-gradient opacity-20 blur-3xl"></div>
                    </div>

                    {{-- Escrow protection card --}}
                    <div class="glass rounded-3xl p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="relative grid h-12 w-12 place-items-center rounded-2xl bg-lyvo-gradient text-white">
                                    <x-icon name="shield-check" class="h-6 w-6" />
                                    <span class="absolute inset-0 -z-10 animate-pulse-ring rounded-2xl bg-primary-400"></span>
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-ink">Escrow Protection</p>
                                    <p class="text-xs text-ink-muted">Funds held securely</p>
                                </div>
                            </div>
                            <span class="badge-verified">Active</span>
                        </div>

                        {{-- Mini pipeline --}}
                        <div class="mt-6 space-y-3">
                            @foreach ([['Payment Initiated', true], ['Funds Held Securely', true], ['Seller Processing', true], ['Delivered', false], ['Funds Released', false]] as [$label, $done])
                                <div class="flex items-center gap-3">
                                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full {{ $done ? 'bg-primary-500 text-white' : 'border-2 border-slate-200 text-transparent' }}">
                                        <x-icon name="check" class="h-3.5 w-3.5" />
                                    </span>
                                    <span class="text-sm {{ $done ? 'font-medium text-ink' : 'text-ink-muted' }}">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 flex items-center justify-between rounded-2xl bg-ink p-4 text-white">
                            <div>
                                <p class="text-xs text-slate-400">Amount in escrow</p>
                                <p class="font-display text-xl font-bold">GH₵ 850.00</p>
                            </div>
                            <x-icon name="lock" class="h-6 w-6 text-primary-400" />
                        </div>
                    </div>

                    {{-- Floating verified badge --}}
                    <div class="absolute -right-4 -top-4 hidden animate-float rounded-2xl bg-white p-3 shadow-card sm:block" style="animation-delay: -2s">
                        <div class="flex items-center gap-2">
                            <x-icon name="id-card" class="h-5 w-5 text-brand-blue" />
                            <span class="text-xs font-semibold text-ink">Ghana Card Verified</span>
                        </div>
                    </div>
                    <div class="absolute -bottom-5 -left-5 hidden animate-float rounded-2xl bg-white p-3 shadow-card sm:block" style="animation-delay: -4s">
                        <div class="flex items-center gap-2">
                            <x-icon name="video" class="h-5 w-5 text-brand-teal" />
                            <span class="text-xs font-semibold text-ink">Video Verified</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================== TRUST PILLARS ============================== --}}
    <section id="trust" class="section bg-white">
        <div class="container-lyvo">
            <div class="mx-auto max-w-2xl text-center">
                <span class="eyebrow"><x-icon name="shield" class="h-4 w-4" /> Why LYVO</span>
                <h2 class="mt-5 font-display text-3xl font-bold text-ink sm:text-4xl">Built on verification, secured by escrow</h2>
                <p class="mt-4 text-ink-soft/80">Every operator is verified before they can sell. Every transaction is protected before funds move.</p>
            </div>

            <div class="mt-14 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['id-card', 'Ghana Card Verified', 'Operators register with their Ghana Card. Identity is checked against the national database before approval.'],
                    ['video', 'Video Verification', 'A live or recorded video confirms the operator is a real person behind a real business — reducing fraud.'],
                    ['shield-check', 'Escrow Protection', 'Your payment is held securely by LYVO and only released to the operator once you confirm delivery.'],
                ] as [$icon, $title, $desc])
                    <div class="card card-hover p-7">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-lyvo-gradient-soft text-primary-600">
                            <x-icon name="{{ $icon }}" class="h-6 w-6" />
                        </span>
                        <h3 class="mt-5 text-lg font-semibold text-ink">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-soft/80">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================== HOW IT WORKS (ESCROW) ============================== --}}
    <section id="how-it-works" class="section bg-surface-muted">
        <div class="container-lyvo">
            <div class="mx-auto max-w-2xl text-center">
                <span class="eyebrow"><x-icon name="wallet" class="h-4 w-4" /> The Escrow Flow</span>
                <h2 class="mt-5 font-display text-3xl font-bold text-ink sm:text-4xl">How a protected transaction works</h2>
                <p class="mt-4 text-ink-soft/80">Five simple steps keep both buyers and operators safe from start to finish.</p>
            </div>

            <div class="relative mt-14">
                <div class="absolute left-0 right-0 top-7 hidden h-0.5 bg-gradient-to-r from-brand-blue via-brand-teal to-brand-green lg:block"></div>
                <div class="grid gap-8 lg:grid-cols-5">
                    @foreach ($pipeline as $i => $step)
                        <div class="relative text-center">
                            <span class="relative z-10 mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-white font-display text-lg font-bold text-primary-600 shadow-soft ring-1 ring-slate-100">
                                {{ $i + 1 }}
                            </span>
                            <h3 class="mt-4 text-sm font-semibold text-ink">{{ $step['label'] }}</h3>
                            <p class="mt-1 text-xs text-ink-muted">{{ $step['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-12 text-center">
                <a href="{{ route('escrow.index') }}" class="btn-dark">
                    See the escrow demo
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>
        </div>
    </section>

    {{-- ============================== CATEGORIES ============================== --}}
    <section id="categories" class="section bg-white">
        <div class="container-lyvo">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <span class="eyebrow"><x-icon name="globe" class="h-4 w-4" /> Explore</span>
                    <h2 class="mt-5 font-display text-3xl font-bold text-ink sm:text-4xl">Browse by category</h2>
                </div>
                <a href="{{ route('directory.index') }}" class="btn-ghost btn-sm">View all <x-icon name="arrow-right" class="h-4 w-4" /></a>
            </div>

            <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($categories as $category)
                    <a href="{{ route('directory.index', ['category' => $category['slug']]) }}" class="card card-hover group flex items-center gap-4 p-5">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-lyvo-gradient-soft text-primary-600 transition group-hover:bg-lyvo-gradient group-hover:text-white">
                            <x-icon name="{{ $category['icon'] }}" class="h-6 w-6" />
                        </span>
                        <div>
                            <p class="font-semibold text-ink">{{ $category['name'] }}</p>
                            <p class="text-xs text-ink-muted">{{ $category['count'] }} operators</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================== FEATURED OPERATORS ============================== --}}
    <section class="section bg-surface-muted">
        <div class="container-lyvo">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <span class="eyebrow"><x-icon name="trending" class="h-4 w-4" /> Trending</span>
                    <h2 class="mt-5 font-display text-3xl font-bold text-ink sm:text-4xl">Top-rated verified operators</h2>
                </div>
                <a href="{{ route('directory.index') }}" class="btn-ghost btn-sm">See directory <x-icon name="arrow-right" class="h-4 w-4" /></a>
            </div>

            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (array_slice($operators, 0, 6) as $operator)
                    <x-operator-card :operator="$operator" />
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================== TRUST SCORE ============================== --}}
    <section id="trust-score" class="section bg-white">
        <div class="container-lyvo grid items-center gap-12 lg:grid-cols-2">
            <div>
                <span class="eyebrow"><x-icon name="badge" class="h-4 w-4" /> Trust Score</span>
                <h2 class="mt-5 font-display text-3xl font-bold text-ink sm:text-4xl">A dynamic score you can trust</h2>
                <p class="mt-4 text-ink-soft/80">
                    Every operator earns a Trust Score from 0–100 based on verification level, reviews,
                    successful transactions, and account age — so you always know who you're dealing with.
                </p>

                <div class="mt-8 space-y-3">
                    @foreach ($trustLevels as $level)
                        <div class="flex items-center justify-between rounded-2xl border border-slate-100 bg-surface-muted px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <span class="h-2.5 w-2.5 rounded-full {{ $level['dot'] }}"></span>
                                <span class="font-semibold {{ $level['color'] }}">{{ $level['label'] }}</span>
                            </div>
                            <span class="text-sm font-medium text-ink-muted">{{ $level['range'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Score gauge --}}
            <div class="relative mx-auto">
                <div class="card relative grid h-72 w-72 place-items-center rounded-full p-2">
                    <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="#F5F7FA" stroke-width="12" />
                        <circle cx="60" cy="60" r="52" fill="none" stroke="url(#g)" stroke-width="12" stroke-linecap="round"
                                stroke-dasharray="326" stroke-dashoffset="20" />
                        <defs>
                            <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#0B5FA5" />
                                <stop offset="0.5" stop-color="#0F9B8E" />
                                <stop offset="1" stop-color="#0EA86F" />
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="absolute text-center">
                        <p class="font-display text-5xl font-extrabold text-ink">96</p>
                        <p class="badge-verified mt-2">Trusted Operator</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================== CTA ============================== --}}
    <section class="section">
        <div class="container-lyvo">
            <div class="relative overflow-hidden rounded-3xl bg-lyvo-gradient px-6 py-16 text-center sm:px-16">
                <div class="absolute inset-0 bg-lyvo-radial opacity-40"></div>
                <div class="relative mx-auto max-w-2xl">
                    <h2 class="font-display text-3xl font-bold text-white sm:text-4xl">Ready to trade with confidence?</h2>
                    <p class="mt-4 text-white/85">Join thousands of customers and verified operators on Ghana's trust layer for digital commerce.</p>
                    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <a href="{{ route('register') }}" class="btn bg-white text-primary-700 hover:bg-white/90">Create a free account</a>
                        <a href="{{ route('register.operator') }}" class="btn border border-white/40 text-white hover:bg-white/10">Become an Operator</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

</x-layouts.public>
