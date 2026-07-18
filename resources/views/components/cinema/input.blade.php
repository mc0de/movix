@props([
    'label' => null,
])

{{-- Cinematic input — extends flux:input with the glass-on-stage treatment. --}}
<flux:input :label="$label" {{ $attributes->class('cinema-input') }} />
