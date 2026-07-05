@props([
    'status', // App\Enums\OrderStatus
])

@php
    $map = [
        'slate'   => 'bg-slate-100 text-slate-600',
        'sky'     => 'bg-sky-50 text-sky-700',
        'amber'   => 'bg-amber-50 text-amber-700',
        'indigo'  => 'bg-indigo-50 text-indigo-700',
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'rose'    => 'bg-rose-50 text-rose-700',
    ];
    $class = $map[$status->color()] ?? $map['slate'];
@endphp

<span {{ $attributes->merge(['class' => "badge $class"]) }}>{{ $status->label() }}</span>
