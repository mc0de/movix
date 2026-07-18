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

test('playing a file keeps the current folder so back returns to it', function () {
    Storage::disk('movies')->put('Season 1/movie.mp4', 'data');

    Livewire::test(MovieBrowser::class)
        ->call('open', 'Season 1')
        ->call('play', 'Season 1/movie.mp4')
        ->assertSet('path', 'Season 1')
        ->assertSet('playing', 'Season 1/movie.mp4')
        // The back button pops the playback history entry, clearing `playing`
        // while leaving the folder in place.
        ->set('playing', null)
        ->assertSet('path', 'Season 1')
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

test('creates a folder in the current location', function () {
    Storage::disk('movies')->makeDirectory('Action');

    Livewire::test(MovieBrowser::class, ['path' => 'Action'])
        ->call('startCreateFolder')
        ->set('newFolderName', 'Sequels')
        ->call('createFolder')
        ->assertHasNoErrors()
        ->assertSet('showNewFolderModal', false);

    expect(Storage::disk('movies')->directoryExists('Action/Sequels'))->toBeTrue();
});

test('create folder requires a name', function () {
    Livewire::test(MovieBrowser::class)
        ->call('createFolder')
        ->assertHasErrors('newFolderName');
});

test('create folder rejects names containing a slash', function () {
    Livewire::test(MovieBrowser::class)
        ->set('newFolderName', 'a/b')
        ->call('createFolder')
        ->assertHasErrors('newFolderName');

    expect(Storage::disk('movies')->directoryExists('a'))->toBeFalse();
});

test('create folder rejects a name that already exists', function () {
    Storage::disk('movies')->makeDirectory('Drama');

    Livewire::test(MovieBrowser::class)
        ->set('newFolderName', 'Drama')
        ->call('createFolder')
        ->assertHasErrors('newFolderName');
});

test('files are sorted by name ascending by default', function () {
    Storage::disk('movies')->put('Banana.mp4', 'x');
    Storage::disk('movies')->put('apple.mp4', 'x');
    Storage::disk('movies')->put('Cherry.mp4', 'x');

    $files = Livewire::test(MovieBrowser::class)->get('files');

    expect(array_column($files, 'name'))->toBe(['apple.mp4', 'Banana.mp4', 'Cherry.mp4']);
});

test('sorts files by size', function () {
    Storage::disk('movies')->put('small.mp4', str_repeat('x', 10));
    Storage::disk('movies')->put('big.mp4', str_repeat('x', 1000));
    Storage::disk('movies')->put('medium.mp4', str_repeat('x', 100));

    $files = Livewire::test(MovieBrowser::class)
        ->call('sortBy', 'size')
        ->assertSet('sortColumn', 'size')
        ->assertSet('sortDirection', 'asc')
        ->get('files');

    expect(array_column($files, 'name'))->toBe(['small.mp4', 'medium.mp4', 'big.mp4']);
});

test('clicking the active column flips the sort direction', function () {
    Storage::disk('movies')->put('a.mp4', 'x');
    Storage::disk('movies')->put('b.mp4', 'x');

    $files = Livewire::test(MovieBrowser::class)
        ->call('sortBy', 'name')
        ->assertSet('sortColumn', 'name')
        ->assertSet('sortDirection', 'desc')
        ->get('files');

    expect(array_column($files, 'name'))->toBe(['b.mp4', 'a.mp4']);
});

test('restoreSort applies a saved sort', function () {
    Storage::disk('movies')->put('a.mp4', str_repeat('x', 100));
    Storage::disk('movies')->put('b.mp4', str_repeat('x', 10));

    $files = Livewire::test(MovieBrowser::class)
        ->call('restoreSort', 'size', 'desc')
        ->assertSet('sortColumn', 'size')
        ->assertSet('sortDirection', 'desc')
        ->get('files');

    expect(array_column($files, 'name'))->toBe(['a.mp4', 'b.mp4']);
});

test('sortBy ignores unknown columns', function () {
    Livewire::test(MovieBrowser::class)
        ->call('sortBy', 'bogus')
        ->assertSet('sortColumn', 'name')
        ->assertSet('sortDirection', 'asc');
});

test('search filters files and folders in the current folder', function () {
    Storage::disk('movies')->put('The Matrix.mp4', 'x');
    Storage::disk('movies')->put('Inception.mp4', 'x');
    Storage::disk('movies')->makeDirectory('Marvel');
    Storage::disk('movies')->makeDirectory('Comedy');

    $component = Livewire::test(MovieBrowser::class)->set('search', 'ma');

    expect(array_column($component->get('files'), 'name'))->toBe(['The Matrix.mp4'])
        ->and(array_column($component->get('directories'), 'name'))->toBe(['Marvel']);
});

test('toggleSelect adds and removes an item from the selection', function () {
    Storage::disk('movies')->put('movie.mp4', 'x');

    Livewire::test(MovieBrowser::class)
        ->call('toggleSelect', 'movie.mp4')
        ->assertSet('selected', ['movie.mp4'])
        ->call('toggleSelect', 'movie.mp4')
        ->assertSet('selected', []);
});

test('toggleSelectAll selects every visible item then clears them', function () {
    Storage::disk('movies')->put('a.mp4', 'x');
    Storage::disk('movies')->put('b.mp4', 'x');
    Storage::disk('movies')->makeDirectory('Folder');

    $component = Livewire::test(MovieBrowser::class)
        ->call('toggleSelectAll');

    expect($component->get('selected'))->toEqualCanonicalizing(['a.mp4', 'b.mp4', 'Folder']);

    $component->call('toggleSelectAll')->assertSet('selected', []);
});

test('select all respects the active search filter', function () {
    Storage::disk('movies')->put('Alpha.mp4', 'x');
    Storage::disk('movies')->put('Beta.mp4', 'x');

    $component = Livewire::test(MovieBrowser::class)
        ->set('search', 'alpha')
        ->call('toggleSelectAll');

    expect($component->get('selected'))->toBe(['Alpha.mp4']);
});

test('moves several selected items into another folder at once', function () {
    Storage::disk('movies')->put('one.mp4', 'x');
    Storage::disk('movies')->put('two.mp4', 'x');
    Storage::disk('movies')->makeDirectory('Archive');

    Livewire::test(MovieBrowser::class)
        ->set('selected', ['one.mp4', 'two.mp4'])
        ->call('startMoveSelected')
        ->assertSet('showMoveModal', true)
        ->set('moveDestination', 'Archive')
        ->call('move')
        ->assertHasNoErrors()
        ->assertSet('showMoveModal', false)
        ->assertSet('selected', []);

    Storage::disk('movies')->assertExists('Archive/one.mp4');
    Storage::disk('movies')->assertExists('Archive/two.mp4');
    Storage::disk('movies')->assertMissing('one.mp4');
    Storage::disk('movies')->assertMissing('two.mp4');
});

test('batch move folder options exclude every selected folder', function () {
    Storage::disk('movies')->makeDirectory('First/Inner');
    Storage::disk('movies')->makeDirectory('Second');
    Storage::disk('movies')->makeDirectory('Third');

    $options = Livewire::test(MovieBrowser::class)
        ->set('selected', ['First', 'Second'])
        ->call('startMoveSelected')
        ->get('moveFolderOptions');

    expect($options)->toContain('Third')
        ->not->toContain('First')
        ->not->toContain('First/Inner')
        ->not->toContain('Second');
});

test('deletes several selected items at once', function () {
    Storage::disk('movies')->put('one.mp4', 'x');
    Storage::disk('movies')->put('Season 1/ep1.mp4', 'x');

    Livewire::test(MovieBrowser::class)
        ->set('selected', ['one.mp4', 'Season 1'])
        ->call('confirmDeleteSelected')
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertSet('showDeleteModal', false)
        ->assertSet('selected', []);

    Storage::disk('movies')->assertMissing('one.mp4');
    expect(Storage::disk('movies')->directoryExists('Season 1'))->toBeFalse();
});

test('startMoveSelected does nothing without a selection', function () {
    Livewire::test(MovieBrowser::class)
        ->call('startMoveSelected')
        ->assertSet('showMoveModal', false);
});

test('confirmDeleteSelected does nothing without a selection', function () {
    Livewire::test(MovieBrowser::class)
        ->call('confirmDeleteSelected')
        ->assertSet('showDeleteModal', false);
});

test('navigating to another folder clears the selection and search', function () {
    Storage::disk('movies')->put('Action/movie.mp4', 'x');

    Livewire::test(MovieBrowser::class)
        ->set('selected', ['Action'])
        ->set('search', 'mo')
        ->call('open', 'Action')
        ->assertSet('path', 'Action')
        ->assertSet('selected', [])
        ->assertSet('search', '');
});

test('deleting the last selected playing file stops playback', function () {
    Storage::disk('movies')->put('movie.mp4', 'x');

    Livewire::test(MovieBrowser::class)
        ->call('play', 'movie.mp4')
        ->set('selected', ['movie.mp4'])
        ->call('confirmDeleteSelected')
        ->call('delete')
        ->assertSet('playing', null);
});
