@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-xl bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700']) }}>
        {{ $status }}
    </div>
@endif
