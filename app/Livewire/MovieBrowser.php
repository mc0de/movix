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

#[Title('Movies')]
class MovieBrowser extends Component
{
    use WithFileUploads;

    /**
     * The current folder being browsed, relative to the "movies" disk root.
     */
    #[Url]
    public string $path = '';

    /**
     * The file currently selected for playback, relative to the disk root.
     */
    public ?string $playing = null;

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
     * The item (file or folder) targeted by the move dialog.
     */
    public ?string $moveTarget = null;

    public string $moveDestination = '';

    public bool $showMoveModal = false;

    /**
     * The item (file or folder) targeted by the delete dialog.
     */
    public ?string $deleteTarget = null;

    public bool $deleteTargetIsDirectory = false;

    public bool $showDeleteModal = false;

    /**
     * The directories within the current folder.
     *
     * @return array<int, array{name: string, path: string, count: int}>
     */
    #[Computed]
    public function directories(): array
    {
        $disk = Storage::disk('movies');

        return collect($disk->directories($this->path))
            ->map(fn (string $directory) => [
                'name' => basename($directory),
                'path' => $directory,
                'count' => count($disk->files($directory)) + count($disk->directories($directory)),
            ])
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

        return collect($disk->files($this->path))
            ->filter(fn (string $file) => Str::endsWith(Str::lower($file), ['.mp4', '.webm', '.ogg', '.mov']))
            ->map(fn (string $file) => [
                'name' => basename($file),
                'path' => $file,
                'size' => Number::fileSize($disk->size($file), precision: 1),
                'kind' => Str::upper(Str::afterLast($file, '.')).' Video',
            ])
            ->values()
            ->all();
    }

    /**
     * The breadcrumb segments for the current path.
     *
     * @return array<int, array{name: string, path: string}>
     */
    #[Computed]
    public function breadcrumbs(): array
    {
        $crumbs = [];
        $accumulated = '';

        foreach (array_filter(explode('/', $this->path)) as $segment) {
            $accumulated = ltrim($accumulated.'/'.$segment, '/');
            $crumbs[] = ['name' => $segment, 'path' => $accumulated];
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
     * Every folder on the disk that the move target can be moved into.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function moveFolderOptions(): array
    {
        $target = $this->moveTarget;

        return collect(Storage::disk('movies')->allDirectories())
            ->reject(fn (string $folder) => $target !== null && ($folder === $target || Str::startsWith($folder, $target.'/')))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Navigate into the given folder.
     */
    public function open(string $path): void
    {
        $this->reset('playing');
        $this->path = $path;
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
     * Store each freshly selected upload into the current folder.
     */
    public function updatedUploads(): void
    {
        $this->validate([
            'uploads' => ['array'],
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
     * Open the rename dialog for the given file or folder.
     */
    public function startRename(string $path): void
    {
        $this->resetErrorBag();
        $this->renameTarget = $path;
        $this->renameValue = basename($path);
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

        $target = (string) $this->renameTarget;
        $directory = Str::contains($target, '/') ? Str::beforeLast($target, '/') : '';
        $destination = ltrim($directory.'/'.$this->renameValue, '/');

        if ($destination !== $target && $this->pathExists($destination)) {
            $this->addError('renameValue', __('An item with that name already exists.'));

            return;
        }

        Storage::disk('movies')->move($target, $destination);

        $this->afterMutation($target, $destination);
        $this->showRenameModal = false;
    }

    /**
     * Open the move dialog for the given file or folder.
     */
    public function startMove(string $path): void
    {
        $this->resetErrorBag();
        $this->moveTarget = $path;
        $this->moveDestination = Str::contains($path, '/') ? Str::beforeLast($path, '/') : '';
        $this->showMoveModal = true;
    }

    /**
     * Move the targeted file or folder into the chosen destination folder.
     */
    public function move(): void
    {
        $this->assertSafe($this->moveTarget);

        if (Str::contains($this->moveDestination, '..')) {
            abort(404);
        }

        $target = (string) $this->moveTarget;
        $destination = ltrim($this->moveDestination.'/'.basename($target), '/');

        if ($destination === $target) {
            $this->showMoveModal = false;

            return;
        }

        if ($this->pathExists($destination)) {
            $this->addError('moveDestination', __('An item with that name already exists in that folder.'));

            return;
        }

        Storage::disk('movies')->move($target, $destination);

        $this->afterMutation($target, $destination);
        $this->showMoveModal = false;
    }

    /**
     * Open the delete confirmation dialog for the given file or folder.
     */
    public function confirmDelete(string $path, bool $isDirectory): void
    {
        $this->deleteTarget = $path;
        $this->deleteTargetIsDirectory = $isDirectory;
        $this->showDeleteModal = true;
    }

    /**
     * Delete the targeted file, or folder and all of its contents.
     */
    public function delete(): void
    {
        $this->assertSafe($this->deleteTarget);

        $target = (string) $this->deleteTarget;
        $disk = Storage::disk('movies');

        if ($this->deleteTargetIsDirectory) {
            $disk->deleteDirectory($target);
        } else {
            $disk->delete($target);
        }

        if ($this->playing === $target || ($this->deleteTargetIsDirectory && Str::startsWith((string) $this->playing, $target.'/'))) {
            $this->reset('playing');
        }

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
        $disk = Storage::disk('movies');
        $extension = Str::contains($name, '.') ? '.'.Str::afterLast($name, '.') : '';
        $base = Str::beforeLast($name, '.');
        $candidate = $name;
        $counter = 1;

        while ($disk->exists(ltrim($directory.'/'.$candidate, '/'))) {
            $candidate = $base.' ('.$counter.')'.$extension;
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
        } elseif (Str::startsWith((string) $this->playing, $from.'/')) {
            $this->playing = $to.Str::after((string) $this->playing, $from);
        }

        $this->refreshListing();
    }

    /**
     * Drop the cached directory/file listings so they recompute on render.
     */
    private function refreshListing(): void
    {
        unset($this->directories, $this->files, $this->moveFolderOptions);
    }
}
