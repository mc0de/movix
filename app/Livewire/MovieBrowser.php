<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * @property-read array<int, array{name: string, path: string, count: int}> $directories
 * @property-read array<int, array{name: string, path: string, size: string, kind: string}> $files
 * @property-read array<int, string> $visiblePaths
 * @property-read bool $allVisibleSelected
 * @property-read bool $someVisibleSelected
 * @property-read array<int, array{name: string, path: string}> $breadcrumbs
 * @property-read string|null $parent
 * @property-read array<int, string> $moveFolderOptions
 * @property-read bool $deletingDirectory
 */
#[Title('Movies')]
class MovieBrowser extends Component
{
    use WithFileUploads;

    /**
     * The current folder being browsed, relative to the "movies" disk root.
     */
    #[Url(history: true)]
    public string $path = '';

    /**
     * A case-insensitive filter applied to the current folder's contents.
     */
    public string $search = '';

    /**
     * The file currently selected for playback, relative to the disk root.
     */
    public ?string $playing = null;

    /**
     * The column files are sorted by: "name", "kind", or "size".
     */
    public string $sortColumn = 'name';

    /**
     * The sort direction: "asc" or "desc".
     */
    public string $sortDirection = 'asc';

    /**
     * The paths of the items currently checked for a batch action.
     *
     * @var array<int, string>
     */
    public array $selected = [];

    /**
     * Freshly selected uploads, stored into the current folder as they arrive.
     *
     * @var array<int, TemporaryUploadedFile>
     */
    public array $uploads = [];

    /**
     * The item (file or folder) targeted by the rename dialog.
     */
    public ?string $renameTarget = null;

    public string $renameValue = '';

    public bool $showRenameModal = false;

    /**
     * The items (files or folders) targeted by the move dialog.
     *
     * @var array<int, string>
     */
    public array $moveTargets = [];

    public string $moveDestination = '';

    public bool $showMoveModal = false;

    /**
     * The items (files or folders) targeted by the delete dialog.
     *
     * @var array<int, string>
     */
    public array $deleteTargets = [];

    public bool $showDeleteModal = false;

    /**
     * The name entered in the new-folder dialog.
     */
    public string $newFolderName = '';

    public bool $showNewFolderModal = false;

    /**
     * The directories within the current folder.
     *
     * @return array<int, array{name: string, path: string, count: int}>
     */
    #[Computed]
    public function directories(): array
    {
        $disk = Storage::disk('movies');

        $directories = collect($disk->directories($this->path))
            ->map(fn (string $directory) => [
                'name'  => basename($directory),
                'path'  => $directory,
                'count' => count($disk->files($directory)) + count($disk->directories($directory)),
            ])
            ->when($this->search !== '', fn ($directories) => $directories->filter(
                fn (array $directory) => Str::contains($directory['name'], $this->search, ignoreCase: true)
            ));

        $descending = $this->sortColumn === 'name' && $this->sortDirection === 'desc';

        return $directories
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE, $descending)
            ->values()
            ->all();
    }

    /**
     * The playable video files within the current folder.
     *
     * @return array<int, array{name: string, path: string, size: string, kind: string}>
     */
    #[Computed]
    public function files(): array
    {
        $disk = Storage::disk('movies');

        $files = collect($disk->files($this->path))
            ->filter(fn (string $file) => Str::endsWith(Str::lower($file), ['.mp4', '.webm', '.ogg', '.mov']))
            ->map(function (string $file) use ($disk) {
                $bytes = $disk->size($file);

                return [
                    'name'  => basename($file),
                    'path'  => $file,
                    'bytes' => $bytes,
                    'size'  => Number::fileSize($bytes, precision: 1),
                    'kind'  => Str::upper(Str::afterLast($file, '.')) . ' Video',
                ];
            })
            ->when($this->search !== '', fn ($files) => $files->filter(
                fn (array $file) => Str::contains($file['name'], $this->search, ignoreCase: true)
            ));

        $descending = $this->sortDirection === 'desc';

        $sorted = match ($this->sortColumn) {
            'size'  => $files->sortBy('bytes', SORT_NUMERIC, $descending),
            'kind'  => $files->sortBy(fn (array $file) => $file['kind'] . ' ' . $file['name'], SORT_NATURAL | SORT_FLAG_CASE, $descending),
            default => $files->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE, $descending),
        };

        return $sorted->values()->all();
    }

    /**
     * The paths of every file and folder currently visible in the listing.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function visiblePaths(): array
    {
        return array_merge(
            array_column($this->directories, 'path'),
            array_column($this->files, 'path'),
        );
    }

    /**
     * Whether every visible item is currently selected.
     */
    #[Computed]
    public function allVisibleSelected(): bool
    {
        $visible = $this->visiblePaths;

        return $visible !== [] && count(array_intersect($visible, $this->selected)) === count($visible);
    }

    /**
     * Whether at least one visible item is currently selected.
     */
    #[Computed]
    public function someVisibleSelected(): bool
    {
        return count(array_intersect($this->visiblePaths, $this->selected)) > 0;
    }

    /**
     * The breadcrumb segments for the current path.
     *
     * @return array<int, array{name: string, path: string}>
     */
    #[Computed]
    public function breadcrumbs(): array
    {
        $crumbs      = [];
        $accumulated = '';

        foreach (array_filter(explode('/', $this->path)) as $segment) {
            $accumulated = ltrim($accumulated . '/' . $segment, '/');
            $crumbs[]    = ['name' => $segment, 'path' => $accumulated];
        }

        return $crumbs;
    }

    /**
     * The parent folder of the current path, or null when at the root.
     */
    #[Computed]
    public function parent(): ?string
    {
        if ($this->path === '') {
            return null;
        }

        return Str::contains($this->path, '/') ? Str::beforeLast($this->path, '/') : '';
    }

    /**
     * Every folder on the disk that the move targets can be moved into.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function moveFolderOptions(): array
    {
        $targets = $this->moveTargets;

        return collect(Storage::disk('movies')->allDirectories())
            ->reject(fn (string $folder) => collect($targets)->contains(
                fn (string $target) => $folder === $target || Str::startsWith($folder, $target . '/')
            ))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Whether any item queued for deletion is a directory.
     */
    #[Computed]
    public function deletingDirectory(): bool
    {
        $disk = Storage::disk('movies');

        return collect($this->deleteTargets)
            ->reject(fn (string $target) => $target === '' || Str::contains($target, '..'))
            ->contains(fn (string $target) => $disk->directoryExists($target));
    }

    /**
     * Navigate into the given folder.
     */
    public function open(string $path): void
    {
        $this->path = $path;
        $this->reset('playing', 'selected', 'search');
    }

    /**
     * Reset transient view state when the browsed folder changes via the URL,
     * such as the browser's back/forward buttons.
     */
    public function updatedPath(): void
    {
        $this->reset('playing', 'selected', 'search');
    }

    /**
     * Select a file to play in the player.
     */
    public function play(string $file): void
    {
        if (Storage::disk('movies')->exists($file)) {
            $this->playing = $file;
        }
    }

    /**
     * Toggle whether a single item is included in the current selection.
     */
    public function toggleSelect(string $path): void
    {
        if (in_array($path, $this->selected, true)) {
            $this->selected = array_values(array_diff($this->selected, [$path]));
        } else {
            $this->selected[] = $path;
        }
    }

    /**
     * Select every visible item, or clear the selection when all are selected.
     */
    public function toggleSelectAll(): void
    {
        if ($this->allVisibleSelected) {
            $this->selected = array_values(array_diff($this->selected, $this->visiblePaths));
        } else {
            $this->selected = array_values(array_unique(array_merge($this->selected, $this->visiblePaths)));
        }
    }

    /**
     * Clear the current selection.
     */
    public function clearSelection(): void
    {
        $this->reset('selected');
    }

    /**
     * Sort by the given column, flipping the direction when it is already active.
     */
    public function sortBy(string $column): void
    {
        if (! in_array($column, ['name', 'kind', 'size'], true)) {
            return;
        }

        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn    = $column;
            $this->sortDirection = 'asc';
        }

        $this->refreshListing();
    }

    /**
     * Apply a sort restored from the client (e.g. localStorage) on page load.
     */
    public function restoreSort(string $column, string $direction): void
    {
        if (! in_array($column, ['name', 'kind', 'size'], true)) {
            return;
        }

        $this->sortColumn    = $column;
        $this->sortDirection = $direction === 'desc' ? 'desc' : 'asc';

        $this->refreshListing();
    }

    /**
     * Store each freshly selected upload into the current folder.
     */
    public function updatedUploads(): void
    {
        $this->validate([
            'uploads'   => ['array'],
            'uploads.*' => ['required', 'file', 'extensions:mp4,webm,ogg,mov'],
        ]);

        $disk = Storage::disk('movies');

        foreach ($this->uploads as $upload) {
            $name = $this->uniqueName($this->path, $upload->getClientOriginalName());
            $upload->storeAs($this->path, $name, 'movies');
        }

        $this->reset('uploads');
        $this->refreshListing();
    }

    /**
     * Open the dialog for creating a new folder in the current location.
     */
    public function startCreateFolder(): void
    {
        $this->resetErrorBag();
        $this->reset('newFolderName');
        $this->showNewFolderModal = true;
    }

    /**
     * Create a new folder with the entered name inside the current folder.
     */
    public function createFolder(): void
    {
        $this->validate([
            'newFolderName' => ['required', 'string', 'max:255'],
        ]);

        if (Str::contains($this->newFolderName, ['/', '\\'])) {
            $this->addError('newFolderName', __('The name may not contain slashes.'));

            return;
        }

        $destination = ltrim($this->path . '/' . $this->newFolderName, '/');

        if ($this->pathExists($destination)) {
            $this->addError('newFolderName', __('An item with that name already exists.'));

            return;
        }

        Storage::disk('movies')->makeDirectory($destination);

        $this->reset('newFolderName');
        $this->refreshListing();
        $this->showNewFolderModal = false;
    }

    /**
     * Open the rename dialog for the given file or folder.
     */
    public function startRename(string $path): void
    {
        $this->resetErrorBag();
        $this->renameTarget    = $path;
        $this->renameValue     = basename($path);
        $this->showRenameModal = true;
    }

    /**
     * Rename the targeted file or folder within its current directory.
     */
    public function rename(): void
    {
        $this->assertSafe($this->renameTarget);

        $this->validate([
            'renameValue' => ['required', 'string', 'max:255'],
        ]);

        if (Str::contains($this->renameValue, ['/', '\\'])) {
            $this->addError('renameValue', __('The name may not contain slashes.'));

            return;
        }

        $target      = (string) $this->renameTarget;
        $directory   = Str::contains($target, '/') ? Str::beforeLast($target, '/') : '';
        $destination = ltrim($directory . '/' . $this->renameValue, '/');

        if ($destination !== $target && $this->pathExists($destination)) {
            $this->addError('renameValue', __('An item with that name already exists.'));

            return;
        }

        Storage::disk('movies')->move($target, $destination);

        $this->afterMutation($target, $destination);
        $this->showRenameModal = false;
    }

    /**
     * Open the move dialog for a single file or folder.
     */
    public function startMove(string $path): void
    {
        $this->resetErrorBag();
        $this->moveTargets     = [$path];
        $this->moveDestination = Str::contains($path, '/') ? Str::beforeLast($path, '/') : '';
        $this->showMoveModal   = true;
    }

    /**
     * Open the move dialog for every currently selected item.
     */
    public function startMoveSelected(): void
    {
        if ($this->selected === []) {
            return;
        }

        $this->resetErrorBag();
        $this->moveTargets     = $this->selected;
        $this->moveDestination = $this->path;
        $this->showMoveModal   = true;
    }

    /**
     * Move every targeted file or folder into the chosen destination folder.
     */
    public function move(): void
    {
        if ($this->moveTargets === []) {
            $this->showMoveModal = false;

            return;
        }

        if (Str::contains($this->moveDestination, '..')) {
            abort(404);
        }

        $disk = Storage::disk('movies');

        foreach ($this->moveTargets as $target) {
            $this->assertSafe($target);

            $destination = ltrim($this->moveDestination . '/' . basename($target), '/');

            if ($destination === $target) {
                continue;
            }

            if ($this->pathExists($destination)) {
                $this->addError('moveDestination', __('An item named ":name" already exists in that folder.', ['name' => basename($target)]));

                return;
            }

            $disk->move($target, $destination);
            $this->afterMutation($target, $destination);
        }

        $this->clearSelection();
        $this->refreshListing();
        $this->showMoveModal = false;
    }

    /**
     * Open the delete confirmation dialog for a single file or folder.
     */
    public function confirmDelete(string $path, bool $isDirectory = false): void
    {
        $this->deleteTargets   = [$path];
        $this->showDeleteModal = true;
    }

    /**
     * Open the delete confirmation dialog for every currently selected item.
     */
    public function confirmDeleteSelected(): void
    {
        if ($this->selected === []) {
            return;
        }

        $this->deleteTargets   = $this->selected;
        $this->showDeleteModal = true;
    }

    /**
     * Delete every targeted file, or folder and all of its contents.
     */
    public function delete(): void
    {
        $disk = Storage::disk('movies');

        foreach ($this->deleteTargets as $target) {
            $this->assertSafe($target);

            if ($disk->directoryExists($target)) {
                $disk->deleteDirectory($target);
            } else {
                $disk->delete($target);
            }

            if ($this->playing === $target || Str::startsWith((string) $this->playing, $target . '/')) {
                $this->reset('playing');
            }
        }

        $this->clearSelection();
        $this->refreshListing();
        $this->showDeleteModal = false;
    }

    public function render(): View
    {
        return view('livewire.movie-browser');
    }

    /**
     * Guard against a null target or path-traversal outside the disk root.
     */
    private function assertSafe(?string $path): void
    {
        if ($path === null || $path === '' || Str::contains($path, '..')) {
            abort(404);
        }
    }

    /**
     * Determine whether a file or directory already exists at the given path.
     */
    private function pathExists(string $path): bool
    {
        $disk = Storage::disk('movies');

        return $disk->exists($path) || $disk->directoryExists($path);
    }

    /**
     * Append a numeric suffix to a filename until it does not collide within the folder.
     */
    private function uniqueName(string $directory, string $name): string
    {
        $disk      = Storage::disk('movies');
        $extension = Str::contains($name, '.') ? '.' . Str::afterLast($name, '.') : '';
        $base      = Str::beforeLast($name, '.');
        $candidate = $name;
        $counter   = 1;

        while ($disk->exists(ltrim($directory . '/' . $candidate, '/'))) {
            $candidate = $base . ' (' . $counter . ')' . $extension;
            $counter++;
        }

        return $candidate;
    }

    /**
     * Keep the player pointed at a file that has just moved or been renamed.
     */
    private function afterMutation(string $from, string $to): void
    {
        if ($this->playing === $from) {
            $this->playing = $to;
        } elseif (Str::startsWith((string) $this->playing, $from . '/')) {
            $this->playing = $to . Str::after((string) $this->playing, $from);
        }

        $this->refreshListing();
    }

    /**
     * Drop the cached directory/file listings so they recompute on render.
     */
    private function refreshListing(): void
    {
        unset(
            $this->directories,
            $this->files,
            $this->moveFolderOptions,
            $this->visiblePaths,
            $this->allVisibleSelected,
            $this->someVisibleSelected,
        );
    }
}
