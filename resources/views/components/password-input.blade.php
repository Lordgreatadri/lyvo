@props([
    'name' => 'password',
    'id' => null,
    'label' => 'Password',
    'placeholder' => '••••••••',
    'autocomplete' => 'new-password',
    'required' => false,
])

@php $id = $id ?? $name; @endphp

<div x-data="{ show: false }">
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    @endif

    <div class="relative">
        <input id="{{ $id }}" name="{{ $name }}"
               :type="show ? 'text' : 'password'"
               autocomplete="{{ $autocomplete }}"
               @if ($required) required @endif
               {{ $attributes->merge(['class' => 'form-input pr-11']) }}
               placeholder="{{ $placeholder }}" />

        <button type="button" @click="show = !show"
                class="absolute right-2 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-lg text-ink-muted transition hover:text-primary-600"
                :aria-label="show ? 'Hide password' : 'Show password'" tabindex="-1">
            <x-icon name="eye" class="h-5 w-5" x-show="!show" />
            <x-icon name="eye-off" class="h-5 w-5" x-show="show" x-cloak />
        </button>
    </div>

    {{ $slot }}
</div>
