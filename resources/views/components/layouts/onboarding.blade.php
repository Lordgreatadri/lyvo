@props([
    'title' => 'Operator Onboarding',
    'step' => 1,
    'total' => 4,
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
<body class="min-h-screen bg-surface-muted font-sans text-ink antialiased">

    <header class="border-b border-slate-100 bg-white">
        <div class="container-lyvo flex h-18 items-center justify-between">
            <a href="{{ route('home') }}"><x-lyvo-logo class="h-9" /></a>
            <a href="{{ route('home') }}" class="text-sm font-medium text-ink-muted hover:text-ink">Save &amp; exit</a>
        </div>
    </header>

    <main class="container-lyvo max-w-4xl py-10">
        {{ $slot }}
    </main>

</body>
</html>
