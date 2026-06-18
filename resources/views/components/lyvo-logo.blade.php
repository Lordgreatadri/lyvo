@props([
    'class' => 'h-8',
    'mono' => false, // when true, use the white transparent mark for dark backgrounds
])

{{-- The brand image already contains the badge + "LYVO" wordmark, so it is
     rendered as the complete lockup with no additional text beside it. --}}
<img src="{{ asset($mono ? 'assets/images/logo/lyvo.png' : 'assets/images/logo/lyvo.jpeg') }}"
     alt="LYVO — The Trust Layer for Digital Commerce"
     {{ $attributes->merge(['class' => $class . ' w-auto select-none']) }} />


