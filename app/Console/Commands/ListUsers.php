<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\table;

#[Signature('user:list')]
#[Description('List all user accounts')]
class ListUsers extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $users = User::query()->orderBy('id')->get();

        if ($users->isEmpty()) {
            info('No users found. Create one with `php artisan user:create`.');

            return self::SUCCESS;
        }

        table(
            headers: ['ID', 'Name', 'Email', 'Verified', 'Created'],
            rows: $users->map(fn (User $user) => [
                (string) $user->id,
                $user->name,
                $user->email,
                $user->email_verified_at ? 'Yes' : 'No',
                $user->created_at?->toDateTimeString() ?? '',
            ])->all(),
        );

        return self::SUCCESS;
    }
}
