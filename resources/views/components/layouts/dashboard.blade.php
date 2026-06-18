@props([
    'role' => 'customer', // customer | operator | admin
    'title' => 'Dashboard',
    'heading' => null,
    'subheading' => null,
])

@php
    // Navigation maps per role. Routes resolve to Phase 1 placeholder screens.
    $navByRole = [
        'customer' => [
            ['label' => 'Overview',    'icon' => 'home',     'route' => 'customer.dashboard'],
            ['label' => 'Escrow',      'icon' => 'shield',   'route' => 'escrow.index'],
            ['label' => 'Operators',   'icon' => 'users',    'route' => 'directory.index'],
            ['label' => 'Saved',       'icon' => 'bookmark', 'route' => 'customer.dashboard'],
            ['label' => 'Reviews',     'icon' => 'star',     'route' => 'customer.dashboard'],
        ],
        'operator' => [
            ['label' => 'Overview',    'icon' => 'home',     'route' => 'operator.dashboard'],
            ['label' => 'Escrow',      'icon' => 'shield',   'route' => 'escrow.index'],
            ['label' => 'Products',    'icon' => 'box',      'route' => 'operator.dashboard'],
            ['label' => 'Verification','icon' => 'badge',    'route' => 'operator.verification'],
            ['label' => 'Leads',       'icon' => 'inbox',    'route' => 'operator.dashboard'],
        ],
        'admin' => [
            ['label' => 'Overview',      'icon' => 'chart',     'route' => 'admin.dashboard'],
            ['label' => 'Verification',  'icon' => 'badge',     'route' => 'admin.verification'],
            ['label' => 'Users',         'icon' => 'users',     'route' => 'admin.dashboard'],
            ['label' => 'Escrow',        'icon' => 'shield',    'route' => 'admin.dashboard'],
            ['label' => 'Reports',       'icon' => 'flag',      'route' => 'admin.dashboard'],
        ],
    ];

    $nav = $navByRole[$role] ?? $navByRole['customer'];

    $roleMeta = [
        'customer' => ['name' => 'Nana Adjei',   'sub' => 'Customer',          'avatar' => 'from-sky-500 to-blue-600'],
        'operator' => ['name' => 'Adwoa Mensah', 'sub' => 'Verified Operator', 'avatar' => 'from-primary-500 to-brand-teal'],
        'admin'    => ['name' => 'LYVO Admin',   'sub' => 'Administrator',     'avatar' => 'from-ink to-ink-soft'],
    ][$role];
@endphp

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
<body class="min-h-screen bg-surface-muted font-sans text-ink antialiased" x-data="{ sidebar: false }">

    {{-- ============ SIDEBAR (desktop) ============ --}}
    <aside class="fixed inset-y-0 left-0 z-40 hidden w-72 border-r border-slate-100 bg-white lg:flex lg:flex-col">
        <div class="flex h-18 items-center px-6">
            <a href="{{ route('home') }}"><x-lyvo-logo class="h-9" /></a>
        </div>

        <div class="px-4">
            <span class="badge {{ $role === 'admin' ? 'bg-ink text-white' : 'badge-verified' }} w-full justify-center py-2">
                <x-icon name="{{ $role === 'admin' ? 'settings' : 'shield-check' }}" class="h-4 w-4" />
                {{ ucfirst($role) }} Workspace
            </span>
        </div>

        <nav class="mt-6 flex-1 space-y-1 px-4">
            @foreach ($nav as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}" class="side-link {{ $active ? 'side-link-active' : '' }}">
                    <x-icon name="{{ $item['icon'] }}" class="h-5 w-5" />
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="border-t border-slate-100 p-4">
            <div class="flex items-center gap-3 rounded-xl bg-surface-muted p-3">
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br {{ $roleMeta['avatar'] }} text-sm font-bold text-white">
                    {{ \Illuminate\Support\Str::of($roleMeta['name'])->explode(' ')->map(fn ($p) => $p[0])->take(2)->implode('') }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold">{{ $roleMeta['name'] }}</p>
                    <p class="truncate text-xs text-ink-muted">{{ $roleMeta['sub'] }}</p>
                </div>
                <a href="{{ route('home') }}" class="text-ink-muted transition hover:text-rose-500" title="Exit demo">
                    <x-icon name="logout" class="h-5 w-5" />
                </a>
            </div>
        </div>
    </aside>

    {{-- ============ MOBILE SIDEBAR ============ --}}
    <div x-show="sidebar" x-cloak class="fixed inset-0 z-50 lg:hidden">
        <div @click="sidebar = false" x-transition.opacity class="absolute inset-0 bg-ink/40 backdrop-blur-sm"></div>
        <aside x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
               class="absolute inset-y-0 left-0 flex w-72 flex-col bg-white">
            <div class="flex h-18 items-center justify-between px-6">
                <x-lyvo-logo class="h-9" />
                <button @click="sidebar = false" class="text-ink-muted">&times;</button>
            </div>
            <nav class="mt-4 flex-1 space-y-1 px-4">
                @foreach ($nav as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}" class="side-link {{ $active ? 'side-link-active' : '' }}">
                        <x-icon name="{{ $item['icon'] }}" class="h-5 w-5" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </aside>
    </div>

    {{-- ============ MAIN AREA ============ --}}
    <div class="lg:pl-72">
        {{-- Topbar --}}
        <header class="sticky top-0 z-30 flex h-18 items-center gap-4 border-b border-slate-100 bg-white/80 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
            <button @click="sidebar = true" class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 lg:hidden">
                <x-icon name="menu" class="h-5 w-5" />
            </button>

            <div class="hidden flex-1 sm:block">
                <div class="relative max-w-md">
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-ink-muted" />
                    <input type="text" placeholder="Search…" class="form-input pl-10" />
                </div>
            </div>

            <div class="ml-auto flex items-center gap-2">
                <button class="relative grid h-10 w-10 place-items-center rounded-xl border border-slate-200 text-ink-soft hover:text-primary-600">
                    <x-icon name="bell" class="h-5 w-5" />
                    <span class="absolute right-2.5 top-2.5 h-2 w-2 rounded-full bg-primary-500"></span>
                </button>
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br {{ $roleMeta['avatar'] }} text-xs font-bold text-white">
                    {{ \Illuminate\Support\Str::of($roleMeta['name'])->explode(' ')->map(fn ($p) => $p[0])->take(2)->implode('') }}
                </div>
            </div>
        </header>

        {{-- Page header --}}
        @if ($heading)
            <div class="border-b border-slate-100 bg-white px-4 py-6 sm:px-6 lg:px-8">
                <h1 class="font-display text-2xl font-bold text-ink">{{ $heading }}</h1>
                @if ($subheading)
                    <p class="mt-1 text-sm text-ink-muted">{{ $subheading }}</p>
                @endif
            </div>
        @endif

        {{-- Page content --}}
        <div class="px-4 py-6 pb-28 sm:px-6 lg:px-8 lg:pb-10">
            {{ $slot }}
        </div>
    </div>

    {{-- ============ MOBILE BOTTOM NAV ============ --}}
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-100 bg-white/90 backdrop-blur-xl lg:hidden">
        <div class="grid grid-cols-5">
            @foreach (array_slice($nav, 0, 5) as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}" class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-medium {{ $active ? 'text-primary-600' : 'text-ink-muted' }}">
                    <x-icon name="{{ $item['icon'] }}" class="h-5 w-5" />
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </nav>

</body>
</html>
