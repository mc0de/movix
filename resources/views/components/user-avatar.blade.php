<div
    {{ $attributes->class('inline-flex size-8 shrink-0 overflow-hidden rounded-full') }}
    role="img"
    aria-label="{{ $name }}"
    title="{{ $name }}"
>
    <svg viewBox="0 0 5 5" class="size-full" shape-rendering="crispEdges" style="background-color: {{ $background }}">
        @foreach ($cells as $index => $filled)
            @if ($filled)
                <rect x="{{ $index % 5 }}" y="{{ intdiv($index, 5) }}" width="1" height="1" fill="{{ $color }}" />
            @endif
        @endforeach
    </svg>
</div>
