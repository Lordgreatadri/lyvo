<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="LYVO — The Trust Layer for Digital Commerce. Discover verified businesses, protected transactions and trusted operators across Ghana.">

    <title>{{ $title ?? 'LYVO — The Trust Layer for Digital Commerce' }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('assets/images/icons/lyvo.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo/lyvo.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface font-sans text-ink antialiased">

    {{-- ====================== HEADER ====================== --}}
    <header x-data="{ open: false, scrolled: false }"
            @scroll.window="scrolled = (window.pageYOffset > 16)"
            class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
            :class="scrolled ? 'bg-white/80 backdrop-blur-xl shadow-soft' : 'bg-transparent'">
        <nav class="container-lyvo flex h-18 items-center justify-between py-3.5">
            <a href="{{ route('home') }}" class="shrink-0">
                <x-lyvo-logo class="h-9" />
            </a>

            <div class="hidden items-center gap-8 lg:flex">
                <a href="{{ route('directory.index') }}" class="text-sm font-medium text-ink-soft transition hover:text-primary-600">Operators</a>
                <a href="{{ route('home') }}#how-it-works" class="text-sm font-medium text-ink-soft transition hover:text-primary-600">How it works</a>
                <a href="{{ route('home') }}#trust" class="text-sm font-medium text-ink-soft transition hover:text-primary-600">Trust &amp; Escrow</a>
                <a href="{{ route('home') }}#categories" class="text-sm font-medium text-ink-soft transition hover:text-primary-600">Categories</a>
            </div>

            <div class="hidden items-center gap-3 lg:flex">
                <a href="{{ route('login') }}" class="btn-ghost btn-sm">Log in</a>
                <a href="{{ route('register') }}" class="btn-primary btn-sm">
                    Get Started
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>

            {{-- Mobile toggle --}}
            <button @click="open = !open" class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 lg:hidden" aria-label="Toggle menu">
                <x-icon name="menu" class="h-5 w-5 text-ink" />
            </button>
        </nav>

        {{-- Mobile menu --}}
        <div x-show="open" x-cloak x-transition.opacity class="border-t border-slate-100 bg-white px-4 py-4 lg:hidden">
            <div class="flex flex-col gap-1">
                <a href="{{ route('directory.index') }}" class="rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-slate-100">Operators</a>
                <a href="{{ route('home') }}#how-it-works" class="rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-slate-100">How it works</a>
                <a href="{{ route('home') }}#trust" class="rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-slate-100">Trust &amp; Escrow</a>
                <a href="{{ route('home') }}#categories" class="rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-slate-100">Categories</a>
                <div class="mt-3 flex flex-col gap-2">
                    <a href="{{ route('login') }}" class="btn-outline w-full">Log in</a>
                    <a href="{{ route('register') }}" class="btn-primary w-full">Get Started</a>
                </div>
            </div>
        </div>
    </header>

    {{-- ====================== MAIN ====================== --}}
    <main>
        {{ $slot }}
    </main>

    {{-- ====================== FOOTER ====================== --}}
    <footer class="bg-ink text-slate-300">
        <div class="container-lyvo grid gap-12 py-16 md:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <x-lyvo-logo class="h-9" mono />
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-slate-400">
                    LYVO is the trust layer for digital commerce in Ghana — verifying operators through Ghana Card,
                    identity and video checks, and protecting every transaction with escrow.
                </p>
                <div class="mt-6 flex items-center gap-3">
                    <span class="badge bg-white/10 text-white"><x-icon name="shield-check" class="h-4 w-4" /> Escrow Protected</span>
                    <span class="badge bg-white/10 text-white"><x-icon name="id-card" class="h-4 w-4" /> Ghana Card Verified</span>
                </div>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-white">Platform</h4>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="{{ route('directory.index') }}" class="hover:text-white">Browse Operators</a></li>
                    <li><a href="{{ route('register.operator') }}" class="hover:text-white">Become an Operator</a></li>
                    <li><a href="{{ route('home') }}#trust" class="hover:text-white">Escrow System</a></li>
                    <li><a href="{{ route('home') }}#trust-score" class="hover:text-white">Trust Score</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-white">Company</h4>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="{{ route('home') }}#how-it-works" class="hover:text-white">How it works</a></li>
                    <li><a href="#" class="hover:text-white">About</a></li>
                    <li><a href="#" class="hover:text-white">Careers</a></li>
                    <li><a href="#" class="hover:text-white">Contact</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-white">Legal</h4>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                    <li><a href="#" class="hover:text-white">Terms of Service</a></li>
                    <li><a href="#" class="hover:text-white">Trust &amp; Safety</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="container-lyvo flex flex-col items-center justify-between gap-3 py-6 text-xs text-slate-500 sm:flex-row">
                <p>&copy; {{ date('Y') }} LYVO. Legitimate Yielding Verified Operators.</p>
                <p>The Trust Layer for Digital Commerce.</p>
            </div>
        </div>
    </footer>

</body>
</html>
