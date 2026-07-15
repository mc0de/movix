<?php

use App\Livewire\MovieBrowser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('movies');
});

test('uploads a video into the current folder', function () {
    Storage::disk('movies')->makeDirectory('Action');

    Livewire::test(MovieBrowser::class, ['path' => 'Action'])
        ->set('uploads', [UploadedFile::fake()->create('clip.mp4', 2048, 'video/mp4')])
        ->assertHasNoErrors();

    Storage::disk('movies')->assertExists('Action/clip.mp4');
});

test('rejects non-video uploads', function () {
    Livewire::test(MovieBrowser::class)
        ->set('uploads', [UploadedFile::fake()->create('notes.txt', 10, 'text/plain')])
        ->assertHasErrors('uploads.*');

    Storage::disk('movies')->assertMissing('notes.txt');
});

test('renames a file', function () {
    Storage::disk('movies')->put('old.mp4', 'data');

    Livewire::test(MovieBrowser::class)
        ->call('startRename', 'old.mp4')
        ->set('renameValue', 'new.mp4')
        ->call('rename')
        ->assertHasNoErrors()
        ->assertSet('showRenameModal', false);

    Storage::disk('movies')->assertExists('new.mp4');
    Storage::disk('movies')->assertMissing('old.mp4');
});

test('renames a folder', function () {
    Storage::disk('movies')->put('Old/movie.mp4', 'data');

    Livewire::test(MovieBrowser::class)
        ->call('startRename', 'Old')
        ->set('renameValue', 'New')
        ->call('rename')
        ->assertHasNoErrors();

    Storage::disk('movies')->assertExists('New/movie.mp4');
    Storage::disk('movies')->assertMissing('Old/movie.mp4');
});

test('rename rejects names containing a slash', function () {
    Storage::disk('movies')->put('movie.mp4', 'data');

    Livewire::test(MovieBrowser::class)
        ->call('startRename', 'movie.mp4')
        ->set('renameValue', 'sub/movie.mp4')
        ->call('rename')
        ->assertHasErrors('renameValue');

    Storage::disk('movies')->assertExists('movie.mp4');
});

test('rename rejects a name that already exists', function () {
    Storage::disk('movies')->put('one.mp4', 'data');
    Storage::disk('movies')->put('two.mp4', 'data');

    Livewire::test(MovieBrowser::class)
        ->call('startRename', 'one.mp4')
        ->set('renameValue', 'two.mp4')
        ->call('rename')
        ->assertHasErrors('renameValue');

    Storage::disk('movies')->assertExists('one.mp4');
});

test('moves a file into another folder', function () {
    Storage::disk('movies')->put('movie.mp4', 'data');
    Storage::disk('movies')->makeDirectory('Archive');

    Livewire::test(MovieBrowser::class)
        ->call('startMove', 'movie.mp4')
        ->set('moveDestination', 'Archive')
        ->call('move')
        ->assertHasNoErrors()
        ->assertSet('showMoveModal', false);

    Storage::disk('movies')->assertExists('Archive/movie.mp4');
    Storage::disk('movies')->assertMissing('movie.mp4');
});

test('move folder options exclude the target and its descendants', function () {
    Storage::disk('movies')->makeDirectory('Parent/Child');
    Storage::disk('movies')->makeDirectory('Other');

    $options = Livewire::test(MovieBrowser::class)
        ->call('startMove', 'Parent')
        ->get('moveFolderOptions');

    expect($options)->toContain('Other')
        ->not->toContain('Parent')
        ->not->toContain('Parent/Child');
});

test('deletes a file', function () {
    Storage::disk('movies')->put('movie.mp4', 'data');

    Livewire::test(MovieBrowser::class)
        ->call('confirmDelete', 'movie.mp4', false)
        ->call('delete')
        ->assertSet('showDeleteModal', false);

    Storage::disk('movies')->assertMissing('movie.mp4');
});

test('deletes a folder and its contents', function () {
    Storage::disk('movies')->put('Season 1/ep1.mp4', 'data');

    Livewire::test(MovieBrowser::class)
        ->call('confirmDelete', 'Season 1', true)
        ->call('delete');

    Storage::disk('movies')->assertMissing('Season 1/ep1.mp4');
    expect(Storage::disk('movies')->directoryExists('Season 1'))->toBeFalse();
});

test('deleting the playing file stops playback', function () {
    Storage::disk('movies')->put('movie.mp4', 'data');

    Livewire::test(MovieBrowser::class)
        ->call('play', 'movie.mp4')
        ->assertSet('playing', 'movie.mp4')
        ->call('confirmDelete', 'movie.mp4', false)
        ->call('delete')
        ->assertSet('playing', null);
});

test('renaming the playing file keeps it playing under the new path', function () {
    Storage::disk('movies')->put('movie.mp4', 'data');

    Livewire::test(MovieBrowser::class)
        ->call('play', 'movie.mp4')
        ->call('startRename', 'movie.mp4')
        ->set('renameValue', 'renamed.mp4')
        ->call('rename')
        ->assertSet('playing', 'renamed.mp4');
});

test('mutations reject path traversal', function () {
    Livewire::test(MovieBrowser::class)
        ->call('confirmDelete', '../secret.mp4', false)
        ->call('delete')
        ->assertStatus(404);
});
