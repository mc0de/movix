<div class="flex h-full w-full flex-1 flex-col gap-6">
    {{-- Player: reacts to `playing` state, so it always stops when hidden (close, back, navigate) --}}
    <div
        wire:key="player"
        x-data="{
            theater: false,
            playing: $wire.entangle('playing'),
            get url() {
                return this.playing
                    ? '{{ url('movies') }}/' + this.playing.split('/').map(encodeURIComponent).join('/')
                    : null;
            },
            resume() { this.$nextTick(() => this.$refs.video?.play().catch(() => {})); },
            stop() {
                if (this.$refs.video) { this.$refs.video.pause(); }
                if (document.fullscreenElement) { document.exitFullscreen(); }
            },
            toggleFullscreen() {
                if (document.fullscreenElement) { document.exitFullscreen(); }
                else { this.$refs.video?.requestFullscreen(); }
            },
            onKey(e) {
                if (! this.playing) { return; }
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) { return; }
                if (e.key === 't' || e.key === 'T') { this.theater = ! this.theater; }
                if (e.key === 'f' || e.key === 'F') { this.toggleFullscreen(); }
            },
            close() { this.playing = null; },
        }"
        x-effect="url ? resume() : stop()"
        x-on:keydown.window="onKey($event)"
        x-show="playing"
        x-cloak
    >
        <div class="w-full">
            <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                {{-- Full-width black stage with a set height; video fills it proportionally --}}
                <div class="flex w-full items-center justify-center bg-black transition-all" :class="theater ? 'h-[85vh]' : 'h-[70vh]'">
                    <video
                        x-ref="video"
                        :src="url"
                        wire:ignore
                        class="size-full object-contain"
                        controls
                        playsinline
                        preload="metadata"
                    ></video>
                </div>

                <div class="flex items-center justify-between gap-2 bg-white px-4 py-3 dark:bg-neutral-900">
                    <flux:heading size="sm" class="truncate">{{ $playing ? basename($playing) : '' }}</flux:heading>
                    <div class="flex items-center gap-1">
                        <flux:button size="sm" variant="ghost" icon="rectangle-group" x-on:click="theater = ! theater">
                            <span class="hidden sm:inline">{{ __('Theater') }}</span>
                            <flux:badge size="sm" class="ml-1">T</flux:badge>
                        </flux:button>
                        <flux:button size="sm" variant="ghost" icon="arrows-pointing-out" x-on:click="toggleFullscreen()">
                            <span class="hidden sm:inline">{{ __('Fullscreen') }}</span>
                            <flux:badge size="sm" class="ml-1">F</flux:badge>
                        </flux:button>
                        <flux:button size="sm" variant="ghost" icon="x-mark" x-on:click="close()" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Finder-style window --}}
    <div class="flex flex-1 flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        {{-- Toolbar --}}
        <div class="flex items-center gap-2 border-b border-neutral-200 bg-neutral-50 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800/60">
            <div class="flex items-center gap-1">
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="chevron-left"
                    :disabled="$this->parent === null"
                    wire:click="open('{{ $this->parent ?? '' }}')"
                />
            </div>

            {{-- Path breadcrumb --}}
            <div class="flex min-w-0 items-center gap-1 text-sm text-neutral-600 dark:text-neutral-300">
                <button type="button" wire:click="open('')" class="inline-flex shrink-0 items-center gap-1 rounded px-1.5 py-0.5 font-medium hover:bg-neutral-200/70 dark:hover:bg-neutral-700">
                    <flux:icon.film class="size-4 text-accent" />
                    {{ __('Movies') }}
                </button>
                @foreach ($this->breadcrumbs as $crumb)
                    <flux:icon.chevron-right class="size-3.5 shrink-0 text-neutral-400" />
                    <button type="button" wire:click="open('{{ $crumb['path'] }}')" class="truncate rounded px-1.5 py-0.5 hover:bg-neutral-200/70 dark:hover:bg-neutral-700">
                        {{ $crumb['name'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- List header --}}
        <div class="grid grid-cols-[1fr_8rem_6rem] gap-2 border-b border-neutral-200 px-4 py-1.5 text-xs font-medium text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
            <span>{{ __('Name') }}</span>
            <span class="hidden sm:block">{{ __('Kind') }}</span>
            <span class="text-right">{{ __('Size') }}</span>
        </div>

        {{-- Rows --}}
        <div class="flex-1 divide-y divide-neutral-100 overflow-y-auto dark:divide-neutral-800">
            @foreach ($this->directories as $directory)
                <button
                    type="button"
                    wire:key="dir-{{ $directory['path'] }}"
                    wire:click="open('{{ $directory['path'] }}')"
                    class="grid w-full grid-cols-[1fr_8rem_6rem] items-center gap-2 px-4 py-2 text-left text-sm hover:bg-neutral-100 dark:hover:bg-neutral-800"
                >
                    <span class="flex min-w-0 items-center gap-2">
                        <flux:icon.folder class="size-5 shrink-0 text-sky-500" />
                        <span class="truncate font-medium">{{ $directory['name'] }}</span>
                    </span>
                    <span class="hidden text-neutral-500 sm:block dark:text-neutral-400">{{ __('Folder') }}</span>
                    <span class="text-right text-neutral-400">{{ trans_choice(':count item|:count items', $directory['count'], ['count' => $directory['count']]) }}</span>
                </button>
            @endforeach

            @foreach ($this->files as $file)
                <button
                    type="button"
                    wire:key="file-{{ $file['path'] }}"
                    wire:click="play('{{ $file['path'] }}')"
                    @class([
                        'grid w-full grid-cols-[1fr_8rem_6rem] items-center gap-2 px-4 py-2 text-left text-sm',
                        'bg-accent text-accent-foreground' => $playing === $file['path'],
                        'hover:bg-neutral-100 dark:hover:bg-neutral-800' => $playing !== $file['path'],
                    ])
                >
                    <span class="flex min-w-0 items-center gap-2">
                        <flux:icon.film @class(['size-5 shrink-0', 'text-accent-foreground' => $playing === $file['path'], 'text-neutral-400' => $playing !== $file['path']]) />
                        <span class="truncate font-medium">{{ $file['name'] }}</span>
                    </span>
                    <span @class(['hidden sm:block', 'text-accent-foreground/80' => $playing === $file['path'], 'text-neutral-500 dark:text-neutral-400' => $playing !== $file['path']])>{{ $file['kind'] }}</span>
                    <span @class(['text-right', 'text-accent-foreground/80' => $playing === $file['path'], 'text-neutral-400' => $playing !== $file['path']])>{{ $file['size'] }}</span>
                </button>
            @endforeach

            @if (empty($this->directories) && empty($this->files))
                <div class="flex flex-col items-center gap-2 py-16 text-center">
                    <flux:icon.film class="size-8 text-neutral-300 dark:text-neutral-600" />
                    <flux:text>{{ __('This folder is empty.') }}</flux:text>
                </div>
            @endif
        </div>

        {{-- Status bar --}}
        <div class="border-t border-neutral-200 bg-neutral-50 px-4 py-1.5 text-center text-xs text-neutral-500 dark:border-neutral-700 dark:bg-neutral-800/60 dark:text-neutral-400">
            {{ trans_choice(':count item|:count items', count($this->directories) + count($this->files), ['count' => count($this->directories) + count($this->files)]) }}
        </div>
    </div>
</div>
