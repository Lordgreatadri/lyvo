@props([
    'metric' => [],
])

<div class="card p-5">
    <div class="flex items-center justify-between">
        <span class="grid h-10 w-10 place-items-center rounded-xl bg-lyvo-gradient-soft text-primary-600">
            <x-icon name="{{ $metric['icon'] }}" class="h-5 w-5" />
        </span>
        <span class="text-xs font-medium text-ink-muted">{{ $metric['delta'] }}</span>
    </div>
    <p class="mt-4 font-display text-2xl font-bold text-ink">{{ $metric['value'] }}</p>
    <p class="text-sm text-ink-muted">{{ $metric['label'] }}</p>
</div>
