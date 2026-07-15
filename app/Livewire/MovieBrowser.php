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

#[Title('Movies')]
class MovieBrowser extends Component
{
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

    public function render(): View
    {
        return view('livewire.movie-browser');
    }
}
