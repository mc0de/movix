<div
    class="flex h-full w-full flex-1 flex-col gap-6"
    x-data="{
        sortColumn: $wire.entangle('sortColumn'),
        sortDirection: $wire.entangle('sortDirection'),
        persist() { localStorage.setItem('movix.sort', JSON.stringify({ column: this.sortColumn, direction: this.sortDirection })); },
    }"
    x-init="
        const saved = JSON.parse(localStorage.getItem('movix.sort') || 'null');
        if (saved?.column && (saved.column !== sortColumn || saved.direction !== sortDirection)) {
            $wire.restoreSort(saved.column, saved.direction);
        }
        $watch('sortColumn', () => persist());
        $watch('sortDirection', () => persist());
    "
>
    {{-- Player: reacts to `playing` state, so it always stops when hidden (close, back, navigate) --}}
    {{-- Negative margins cancel the padded `flux:main` container so the video is edge-to-edge. --}}
    <div
        class="-mx-6 -mt-6 flex h-[calc(100vh-3.5rem)] flex-col lg:-mx-8 lg:-mt-8"
        wire:key="player"
        x-data="{
            playing: $wire.entangle('playing'),
            positions: JSON.parse(localStorage.getItem('movix.positions') || '{}'),
            saveTimer: null,
            // Whether starting playback pushed a history entry we can pop to close.
            pushedHistory: false,
            get url() {
                return this.playing
                    ? '{{ url('movies') }}/' + this.playing.split('/').map(encodeURIComponent).join('/')
                    : null;
            },
            init() {
                // Reopen the video that was playing before an accidental refresh (a deliberate close clears this key).
                const last = localStorage.getItem('movix.playing');
                if (last && this.positions[last] && ! this.playing) { this.$wire.play(last); }
                // Track when playback begins from a closed player: that transition is the one
                // that adds a history entry, so closing can pop it to land back on the folder.
                this.$watch('playing', (value, previous) => {
                    if (value && ! previous) { this.pushedHistory = true; }
                    if (! value) { this.pushedHistory = false; }
                });
            },
            resume() { this.$nextTick(() => this.$refs.video?.play().catch(() => {})); },
            stop() {
                if (this.$refs.video) { this.$refs.video.pause(); }
                if (document.fullscreenElement) { document.exitFullscreen(); }
            },
            restorePosition() {
                const at = this.positions[this.playing];
                if (this.$refs.video && at) { this.$refs.video.currentTime = at; }
            },
            savePosition() {
                const v = this.$refs.video;
                if (! this.playing || ! v || ! v.duration) { return; }
                // Forget the position once effectively finished, so it starts fresh next time.
                if (v.currentTime > 1 && v.currentTime < v.duration - 5) { this.positions[this.playing] = v.currentTime; }
                else { delete this.positions[this.playing]; }
                localStorage.setItem('movix.positions', JSON.stringify(this.positions));
            },
            startSaving() {
                localStorage.setItem('movix.playing', this.playing);
                clearInterval(this.saveTimer);
                this.saveTimer = setInterval(() => this.savePosition(), 5000);
            },
            stopSaving() { clearInterval(this.saveTimer); this.saveTimer = null; },
            toggleFullscreen() {
                if (document.fullscreenElement) { document.exitFullscreen(); }
                else { this.$refs.video?.requestFullscreen(); }
            },
            onKey(e) {
                if (! this.playing) { return; }
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) { return; }
                if (e.key === 'f' || e.key === 'F') { this.toggleFullscreen(); }
                if (e.key === 'Escape' && ! document.fullscreenElement) { this.close(); }
            },
            close() {
                this.savePosition();
                this.stopSaving();
                localStorage.removeItem('movix.playing');
                this.stop();
                // Pop the history entry that playback added so Close and ESC behave like Back,
                // landing on the current folder. Fall back to clearing state when there is
                // nothing to pop (e.g. a video opened directly via a shared/bookmarked URL).
                if (this.pushedHistory) {
                    this.pushedHistory = false;
                    window.history.back();
                } else {
                    this.playing = null;
                }
            },
        }"
        x-effect="url ? resume() : stop()"
        x-on:keydown.window="onKey($event)"
        x-on:beforeunload.window="savePosition()"
        x-show="playing"
        x-cloak
    >
        {{-- Black stage grows to fill the space above the title bar; video fits proportionally --}}
        <div class="flex min-h-0 w-full flex-1 items-center justify-center bg-black">
            <video
                x-ref="video"
                :src="url"
                wire:ignore
                class="size-full object-contain"
                controls
                playsinline
                preload="metadata"
                x-on:loadedmetadata="restorePosition()"
                x-on:play="startSaving()"
                x-on:pause="savePosition(); stopSaving()"
                x-on:ended="savePosition(); stopSaving(); localStorage.removeItem('movix.playing')"
            ></video>
        </div>

        {{-- Pinned to the bottom of the player: now-playing title + controls (re-padded to align with page content) --}}
        <div class="flex shrink-0 items-center justify-between gap-3 px-6 py-3 lg:px-8">
            <div class="flex min-w-0 items-center gap-2.5">
                <flux:heading class="truncate font-semibold tabular-nums text-neutral-100">{{ $playing ? basename($playing) : '' }}</flux:heading>
                <span class="hidden shrink-0 text-xs text-neutral-500 sm:block">{{ $playing ? str($playing)->afterLast('.')->upper() : '' }}</span>
            </div>
            <div class="flex shrink-0 items-center gap-1">
                <flux:button size="sm" variant="ghost" icon="arrows-pointing-out" x-on:click="toggleFullscreen()">
                    <span class="hidden sm:inline">{{ __('Fullscreen') }}</span>
                    <flux:badge size="sm" class="ml-1">F</flux:badge>
                </flux:button>
                <flux:button size="sm" variant="ghost" icon="x-mark" x-on:click="close()" />
            </div>
        </div>
    </div>

    {{-- Finder-style window --}}
    <div class="flex flex-1 flex-col overflow-hidden rounded-xl border border-neutral-800 bg-neutral-900 shadow-lg">
        {{-- Toolbar --}}
        <div class="flex items-center gap-2 border-b border-neutral-800 bg-neutral-900/70 px-3 py-2.5 backdrop-blur">
            <button
                type="button"
                @disabled($this->parent === null)
                wire:click="open('{{ $this->parent ?? '' }}')"
                class="flex size-8 shrink-0 items-center justify-center rounded-md text-neutral-400 transition hover:bg-white/5 hover:text-neutral-100 disabled:pointer-events-none disabled:opacity-30"
                aria-label="{{ __('Back') }}"
            >
                <flux:icon.chevron-left class="size-4" />
            </button>

            {{-- Path breadcrumb --}}
            <div class="flex min-w-0 items-center gap-1 text-sm text-neutral-400">
                <button type="button" wire:click="open('')" class="inline-flex shrink-0 items-center gap-1.5 rounded-md px-1.5 py-1 font-medium text-neutral-200 transition hover:bg-white/5">
                    <flux:icon.film variant="solid" class="size-4 text-[#0A84FF]" />
                    {{ __('Movies') }}
                </button>
                @foreach ($this->breadcrumbs as $crumb)
                    <flux:icon.chevron-right class="size-3.5 shrink-0 text-neutral-600" />
                    <button type="button" wire:click="open('{{ $crumb['path'] }}')" class="truncate rounded-md px-1.5 py-1 transition hover:bg-white/5 hover:text-neutral-100">
                        {{ $crumb['name'] }}
                    </button>
                @endforeach
            </div>

            <flux:spacer />

            <div class="flex shrink-0 items-center gap-2">
                {{-- Filter the current folder by name --}}
                <div class="relative hidden md:block">
                    <flux:icon.magnifying-glass class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-neutral-500" />
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search this folder') }}"
                        class="w-48 rounded-md border border-neutral-700 bg-neutral-800 py-1.5 pl-8 pr-3 text-sm text-neutral-200 placeholder-neutral-500 transition focus:border-[#0A84FF] focus:outline-none focus:ring-1 focus:ring-[#0A84FF]"
                    />
                </div>

                {{-- Create a folder in the current location --}}
                <button
                    type="button"
                    wire:click="startCreateFolder"
                    class="inline-flex items-center gap-2 rounded-md border border-neutral-700 bg-neutral-800 px-3 py-1.5 text-sm font-medium text-neutral-200 transition hover:bg-neutral-700"
                >
                    <flux:icon.folder-plus class="size-4" />
                    <span class="hidden sm:inline">{{ __('New Folder') }}</span>
                </button>

                {{-- Upload: streams straight into the current folder, no size limit --}}
                <div x-data>
                    <input
                        type="file"
                        x-ref="uploadInput"
                        wire:model="uploads"
                        multiple
                        accept="video/*,.mp4,.webm,.ogg,.mov"
                        class="hidden"
                    />
                    <button
                        type="button"
                        x-on:click="$refs.uploadInput.click()"
                        wire:loading.attr="disabled"
                        wire:target="uploads"
                        class="inline-flex items-center gap-2 rounded-md bg-[#0A84FF] px-3 py-1.5 text-sm font-medium text-white transition hover:bg-[#0071e3] disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <flux:icon.arrow-up-tray class="size-4" wire:loading.remove wire:target="uploads" />
                        <svg wire:loading wire:target="uploads" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                            <path class="opacity-90" d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                        <span wire:loading.remove wire:target="uploads">{{ __('Upload') }}</span>
                        <span wire:loading wire:target="uploads">{{ __('Uploading…') }}</span>
                    </button>
                </div>
            </div>
        </div>

        @error('uploads.*')
            <div class="flex items-center gap-2 border-b border-red-900/50 bg-red-950/40 px-4 py-2 text-xs text-red-400">
                <flux:icon.exclamation-triangle class="size-4 shrink-0" />
                {{ $message }}
            </div>
        @enderror

        {{-- Selection action bar: appears while one or more items are checked --}}
        @if (count($selected) > 0)
            <div class="flex items-center gap-3 border-b border-[#0A84FF]/40 bg-[#0A84FF]/10 px-4 py-2 text-sm text-neutral-100">
                <span class="font-medium tabular-nums">{{ trans_choice(':count selected|:count selected', count($selected), ['count' => count($selected)]) }}</span>
                <flux:spacer />
                <flux:button size="xs" variant="ghost" icon="folder-arrow-down" wire:click="startMoveSelected">{{ __('Move') }}</flux:button>
                <flux:button size="xs" variant="ghost" icon="trash" wire:click="confirmDeleteSelected">{{ __('Delete') }}</flux:button>
                <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="clearSelection">{{ __('Clear') }}</flux:button>
            </div>
        @endif

        {{-- List header --}}
        <div class="grid grid-cols-[1.75rem_1fr_9rem_6rem_6rem] items-center gap-3 border-b border-neutral-800 px-4 py-2 text-xs font-medium text-neutral-500">
            {{-- Select-all: three states (none / some / all) driven entirely server-side --}}
            <button type="button" wire:click="toggleSelectAll" class="flex items-center justify-center" aria-label="{{ __('Select all') }}">
                <span @class([
                    'flex size-4 items-center justify-center rounded border transition',
                    'border-[#0A84FF] bg-[#0A84FF] text-white' => $this->allVisibleSelected || $this->someVisibleSelected,
                    'border-neutral-600 hover:border-neutral-400' => ! $this->allVisibleSelected && ! $this->someVisibleSelected,
                ])>
                    @if ($this->allVisibleSelected)
                        <flux:icon.check class="size-3" />
                    @elseif ($this->someVisibleSelected)
                        <flux:icon.minus class="size-3" />
                    @endif
                </span>
            </button>
            <button type="button" wire:click="sortBy('name')" @class(['flex items-center gap-1 transition hover:text-neutral-300', 'text-neutral-200' => $sortColumn === 'name'])>
                {{ __('Name') }}
                @if ($sortColumn === 'name')
                    <flux:icon.chevron-up @class(['size-3 transition-transform', 'rotate-180' => $sortDirection === 'desc']) />
                @endif
            </button>
            <button type="button" wire:click="sortBy('kind')" @class(['hidden items-center gap-1 transition hover:text-neutral-300 sm:flex', 'text-neutral-200' => $sortColumn === 'kind'])>
                {{ __('Kind') }}
                @if ($sortColumn === 'kind')
                    <flux:icon.chevron-up @class(['size-3 transition-transform', 'rotate-180' => $sortDirection === 'desc']) />
                @endif
            </button>
            <button type="button" wire:click="sortBy('size')" @class(['flex items-center justify-end gap-1 transition hover:text-neutral-300', 'text-neutral-200' => $sortColumn === 'size'])>
                {{ __('Size') }}
                @if ($sortColumn === 'size')
                    <flux:icon.chevron-up @class(['size-3 transition-transform', 'rotate-180' => $sortDirection === 'desc']) />
                @endif
            </button>
            <span class="sr-only">{{ __('Actions') }}</span>
        </div>

        {{-- Rows --}}
        <div class="flex-1 divide-y divide-white/[0.04] overflow-y-auto">
            @foreach ($this->directories as $directory)
                @php($isSelected = in_array($directory['path'], $selected, true))
                <div
                    wire:key="dir-{{ $directory['path'] }}"
                    @class([
                        'group grid grid-cols-[1.75rem_1fr_9rem_6rem_6rem] items-center gap-3 px-4 text-sm transition-colors',
                        'bg-[#0A84FF]/10' => $isSelected,
                        'hover:bg-white/5' => ! $isSelected,
                    ])
                >
                    {{-- Selection checkbox --}}
                    <button
                        type="button"
                        wire:click="toggleSelect('{{ $directory['path'] }}')"
                        class="flex items-center justify-center"
                        aria-label="{{ __('Select') }}"
                    >
                        <span @class([
                            'flex size-4 items-center justify-center rounded border transition',
                            'border-[#0A84FF] bg-[#0A84FF] text-white' => $isSelected,
                            'border-neutral-500 hover:border-neutral-300' => ! $isSelected,
                        ])>
                            @if ($isSelected)
                                <flux:icon.check class="size-3" />
                            @endif
                        </span>
                    </button>

                    <button
                        type="button"
                        wire:click="open('{{ $directory['path'] }}')"
                        class="col-span-3 grid grid-cols-subgrid items-center gap-3 py-2 text-left"
                    >
                        <span class="flex min-w-0 items-center gap-2.5">
                            <flux:icon.folder variant="solid" class="size-5 shrink-0 text-[#0A84FF]" />
                            <span class="truncate font-medium tabular-nums text-neutral-100">{{ $directory['name'] }}</span>
                        </span>
                        <span class="hidden text-neutral-500 sm:block">{{ __('Folder') }}</span>
                        <span class="text-right tabular-nums text-neutral-500">{{ trans_choice(':count item|:count items', $directory['count'], ['count' => $directory['count']]) }}</span>
                    </button>

                    {{-- Inline actions: rename, move, delete kept visible side by side --}}
                    <div class="flex items-center justify-end gap-0.5 opacity-0 transition group-hover:opacity-100 focus-within:opacity-100">
                        <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="startRename('{{ $directory['path'] }}')" aria-label="{{ __('Rename') }}" />
                        <flux:button size="xs" variant="ghost" icon="folder-arrow-down" wire:click="startMove('{{ $directory['path'] }}')" aria-label="{{ __('Move') }}" />
                        <flux:button size="xs" variant="ghost" icon="trash" wire:click="confirmDelete('{{ $directory['path'] }}')" aria-label="{{ __('Delete') }}" />
                    </div>
                </div>
            @endforeach

            @foreach ($this->files as $file)
                @php($isPlaying = $playing === $file['path'])
                @php($isSelected = in_array($file['path'], $selected, true))
                <div
                    wire:key="file-{{ $file['path'] }}"
                    @class([
                        'group grid grid-cols-[1.75rem_1fr_9rem_6rem_6rem] items-center gap-3 px-4 text-sm transition-colors',
                        'bg-[#0A84FF]' => $isPlaying,
                        'bg-[#0A84FF]/10' => $isSelected && ! $isPlaying,
                        'hover:bg-white/5' => ! $isPlaying && ! $isSelected,
                    ])
                >
                    {{-- Selection checkbox --}}
                    <button
                        type="button"
                        wire:click="toggleSelect('{{ $file['path'] }}')"
                        class="flex items-center justify-center"
                        aria-label="{{ __('Select') }}"
                    >
                        <span @class([
                            'flex size-4 items-center justify-center rounded border transition',
                            'border-white bg-white text-[#0A84FF]' => $isSelected && $isPlaying,
                            'border-white/70' => ! $isSelected && $isPlaying,
                            'border-[#0A84FF] bg-[#0A84FF] text-white' => $isSelected && ! $isPlaying,
                            'border-neutral-500 hover:border-neutral-300' => ! $isSelected && ! $isPlaying,
                        ])>
                            @if ($isSelected)
                                <flux:icon.check class="size-3" />
                            @endif
                        </span>
                    </button>

                    <button
                        type="button"
                        wire:click="play('{{ $file['path'] }}')"
                        class="col-span-3 grid grid-cols-subgrid items-center gap-3 py-2 text-left"
                    >
                        <span class="flex min-w-0 items-center gap-2.5">
                            @if ($isPlaying)
                                <flux:icon.play variant="solid" class="size-5 shrink-0 text-white" />
                            @else
                                <flux:icon.film class="size-5 shrink-0 text-neutral-400" />
                            @endif
                            <span @class([
                                'truncate font-medium tabular-nums',
                                'text-white' => $isPlaying,
                                'text-neutral-100' => ! $isPlaying,
                            ])>{{ $file['name'] }}</span>
                        </span>
                        <span @class([
                            'hidden sm:block',
                            'text-white/70' => $isPlaying,
                            'text-neutral-500' => ! $isPlaying,
                        ])>{{ $file['kind'] }}</span>
                        <span @class([
                            'text-right tabular-nums',
                            'text-white/70' => $isPlaying,
                            'text-neutral-500' => ! $isPlaying,
                        ])>{{ $file['size'] }}</span>
                    </button>

                    {{-- Inline actions: rename, move, delete kept visible side by side --}}
                    <div @class([
                        'flex items-center justify-end gap-0.5 transition',
                        'opacity-100' => $isPlaying,
                        'opacity-0 group-hover:opacity-100 focus-within:opacity-100' => ! $isPlaying,
                    ])>
                        <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="startRename('{{ $file['path'] }}')" aria-label="{{ __('Rename') }}" @class(['text-white' => $isPlaying]) />
                        <flux:button size="xs" variant="ghost" icon="folder-arrow-down" wire:click="startMove('{{ $file['path'] }}')" aria-label="{{ __('Move') }}" @class(['text-white' => $isPlaying]) />
                        <flux:button size="xs" variant="ghost" icon="trash" wire:click="confirmDelete('{{ $file['path'] }}')" aria-label="{{ __('Delete') }}" @class(['text-white' => $isPlaying]) />
                    </div>
                </div>
            @endforeach

            @if (empty($this->directories) && empty($this->files))
                <div class="flex flex-col items-center gap-3 py-20 text-center">
                    @if ($search !== '')
                        <flux:icon.magnifying-glass class="size-10 text-neutral-700" />
                        <flux:heading class="text-base font-medium text-neutral-300">{{ __('No matches') }}</flux:heading>
                        <flux:text class="text-neutral-500">{{ __('Nothing in this folder matches ":search".', ['search' => $search]) }}</flux:text>
                    @else
                        <flux:icon.film class="size-10 text-neutral-700" />
                        <flux:heading class="text-base font-medium text-neutral-300">{{ __('This folder is empty') }}</flux:heading>
                        <flux:text class="text-neutral-500">{{ __('Upload a video to get started.') }}</flux:text>
                    @endif
                </div>
            @endif
        </div>

        {{-- Status bar --}}
        <div class="border-t border-neutral-800 bg-neutral-900/70 px-4 py-2 text-center text-xs text-neutral-500 backdrop-blur">
            {{ trans_choice(':count item|:count items', count($this->directories) + count($this->files), ['count' => count($this->directories) + count($this->files)]) }}
            @if ($search !== '')
                {{ __('matching ":search"', ['search' => $search]) }}
            @endif
        </div>
    </div>

    {{-- New folder dialog --}}
    <flux:modal wire:model.self="showNewFolderModal" class="md:w-96">
        <form wire:submit="createFolder" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('New Folder') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Create a new folder in the current location.') }}</flux:text>
            </div>
            <flux:input wire:model="newFolderName" label="{{ __('Name') }}" placeholder="{{ __('Untitled Folder') }}" autofocus />
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showNewFolderModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Rename dialog --}}
    <flux:modal wire:model.self="showRenameModal" class="md:w-96">
        <form wire:submit="rename" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Rename') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Choose a new name for this item.') }}</flux:text>
            </div>
            <flux:input wire:model="renameValue" label="{{ __('Name') }}" autofocus />
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showRenameModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Rename') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Move dialog --}}
    <flux:modal wire:model.self="showMoveModal" class="md:w-96">
        <form wire:submit="move" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Move') }}</flux:heading>
                <flux:text class="mt-2">
                    @if (count($moveTargets) === 1)
                        {{ __('Move ":name" into another folder.', ['name' => basename($moveTargets[0])]) }}
                    @else
                        {{ __('Move :count items into another folder.', ['count' => count($moveTargets)]) }}
                    @endif
                </flux:text>
            </div>
            <flux:select wire:model="moveDestination" label="{{ __('Destination folder') }}">
                <flux:select.option value="">{{ __('Movies (root)') }}</flux:select.option>
                @foreach ($this->moveFolderOptions as $folder)
                    <flux:select.option value="{{ $folder }}">{{ $folder }}</flux:select.option>
                @endforeach
            </flux:select>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showMoveModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Move') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete confirmation --}}
    <flux:modal wire:model.self="showDeleteModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete') }}</flux:heading>
                <flux:text class="mt-2">
                    @if (count($deleteTargets) === 1)
                        {{ __('Are you sure you want to delete ":name"?', ['name' => basename($deleteTargets[0])]) }}
                    @else
                        {{ __('Are you sure you want to delete these :count items?', ['count' => count($deleteTargets)]) }}
                    @endif
                    @if ($this->deletingDirectory)
                        {{ __('Folders are removed with everything inside them.') }}
                    @endif
                    {{ __('This cannot be undone.') }}
                </flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="delete">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
