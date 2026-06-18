@props([
    'title' => 'Welcome',
    'subtitle' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — LYVO</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/images/icons/lyvo.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo/lyvo.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface font-sans text-ink antialiased">
    <div class="grid min-h-screen lg:grid-cols-2">

        {{-- ====== Brand panel ====== --}}
        <div class="relative hidden overflow-hidden bg-lyvo-gradient lg:block">
            <div class="absolute inset-0 bg-lyvo-radial opacity-60"></div>
            <div class="relative flex h-full flex-col p-12">
                <a href="{{ route('home') }}"><x-lyvo-logo class="h-10" mono /></a>

                <div class="my-auto max-w-md">
                    <h2 class="font-display text-4xl font-extrabold leading-tight text-white">
                        The Trust Layer for Digital Commerce.
                    </h2>
                    <p class="mt-4 text-white/80">
                        Verified operators, protected payments, and a trust score you can rely on — all in one place.
                    </p>

                    <div class="mt-10 space-y-4">
                        @foreach ([['id-card', 'Ghana Card &amp; identity verified'], ['video', 'Live video verification'], ['shield-check', 'Escrow-protected transactions']] as [$icon, $label])
                            <div class="flex items-center gap-3 text-white">
                                <span class="grid h-10 w-10 place-items-center rounded-xl bg-white/15">
                                    <x-icon name="{{ $icon }}" class="h-5 w-5" />
                                </span>
                                <span class="text-sm font-medium">{!! $label !!}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="glass rounded-2xl p-5 text-white">
                    <div class="flex items-center gap-3">
                        <x-icon name="lock" class="h-5 w-5" />
                        <p class="text-sm font-medium">GH₵ 850.00 held securely in escrow · released on delivery</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ====== Form panel ====== --}}
        <div class="flex flex-col px-6 py-8 sm:px-12">
            <div class="flex items-center justify-between lg:hidden">
                <a href="{{ route('home') }}"><x-lyvo-logo class="h-9" /></a>
                <a href="{{ route('home') }}" class="text-sm text-ink-muted hover:text-ink">&larr; Home</a>
            </div>

            <div class="my-auto w-full max-w-md py-8 sm:mx-auto">
                <div class="mb-8">
                    <h1 class="font-display text-3xl font-bold text-ink">{{ $title }}</h1>
                    @if ($subtitle)
                        <p class="mt-2 text-ink-soft/80">{{ $subtitle }}</p>
                    @endif
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
