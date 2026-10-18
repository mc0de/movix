<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

it('lists every user with their verification status', function () {
    User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
    User::factory()->unverified()->create(['name' => 'Alan Turing', 'email' => 'alan@example.com']);

    expect(Artisan::call('user:list'))->toBe(0);

    expect(Artisan::output())
        ->toMatch('/Ada Lovelace\s*│\s*ada@example\.com\s*│\s*Yes/u')
        ->toMatch('/Alan Turing\s*│\s*alan@example\.com\s*│\s*No/u');
});

it('explains how to create a user when there are none', function () {
    $this->artisan('user:list')
        ->expectsOutputToContain('No users found')
        ->assertSuccessful();
});
