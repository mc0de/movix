@props([
    'variant' => 'primary',
])

{{-- Cinematic button — extends flux:button with the brand gradient fill. --}}
<flux:button :variant="$variant" {{ $attributes->class('cinema-btn') }}>
    {{ $slot }}
</flux:button>
