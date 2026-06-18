@props([
    'status' => '',
    'key' => '',
])

@php
    $map = [
        'initiated'  => 'badge-info',
        'held'       => 'badge bg-indigo-50 text-indigo-700',
        'processing' => 'badge-pending',
        'delivered'  => 'badge bg-sky-50 text-sky-700',
        'released'   => 'badge-verified',
        'dispute'    => 'badge-rejected',
    ];
    $class = $map[$key] ?? 'badge bg-slate-100 text-slate-600';
@endphp

<span class="{{ $class }}">{{ $status }}</span>
