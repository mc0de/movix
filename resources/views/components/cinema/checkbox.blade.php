@props([
    'label' => null,
    'checked' => false,
])

{{-- Cinematic checkbox — extends flux:checkbox with a muted on-stage label. --}}
<flux:checkbox :label="$label" :checked="$checked" {{ $attributes->class('cinema-check') }} />
