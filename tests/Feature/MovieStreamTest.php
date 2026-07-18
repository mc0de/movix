<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('movies');
});

test('streams an existing movie file to an authenticated user', function () {
    Storage::disk('movies')->put('Action/clip.mp4', 'video-bytes');

    $this->actingAs(User::factory()->create())
        ->get(route('movies.stream', ['path' => 'Action/clip.mp4']))
        ->assertOk()
        ->assertHeader('accept-ranges', 'bytes');
});

test('serves a byte range when the client requests one', function () {
    Storage::disk('movies')->put('clip.mp4', 'abcdefghij');

    $this->actingAs(User::factory()->create())
        ->get(route('movies.stream', ['path' => 'clip.mp4']), ['Range' => 'bytes=0-4'])
        ->assertStatus(206)
        ->assertHeader('content-range', 'bytes 0-4/10');
});

test('returns 404 for a missing file', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('movies.stream', ['path' => 'nope.mp4']))
        ->assertNotFound();
});

test('returns 404 for a path traversal attempt', function () {
    // A secret one level above the disk root must never be reachable.
    Storage::disk('movies')->put('inside.mp4', 'data');

    $this->actingAs(User::factory()->create())
        ->get(route('movies.stream', ['path' => '../inside.mp4']))
        ->assertNotFound();
});

test('guests cannot stream and are redirected to login', function () {
    Storage::disk('movies')->put('clip.mp4', 'data');

    $this->get(route('movies.stream', ['path' => 'clip.mp4']))
        ->assertRedirect(route('login'));
});
