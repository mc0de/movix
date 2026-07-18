@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Movix" class="text-xl tracking-tight" {{ $attributes }}>
        <x-slot name="logo">
            <x-app-logo-icon class="h-5 w-auto shrink-0" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Movix" class="text-xl tracking-tight" {{ $attributes }}>
        <x-slot name="logo">
            <x-app-logo-icon class="h-5 w-auto shrink-0" />
        </x-slot>
    </flux:brand>
@endif
