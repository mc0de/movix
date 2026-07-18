<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\form;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

#[Signature('user:create')]
#[Description('Create a new user through an interactive prompt')]
class CreateUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        note('Let\'s create a new user account.');

        $responses = form()
            ->text(
                label: 'Name',
                placeholder: 'Ada Lovelace',
                required: true,
                validate: fn (string $value) => $this->validateField('name', $value),
                name: 'name',
            )
            ->text(
                label: 'Email',
                placeholder: 'ada@example.com',
                required: true,
                validate: fn (string $value) => $this->validateField('email', $value),
                name: 'email',
            )
            ->password(
                label: 'Password',
                required: true,
                validate: fn (string $value) => $this->validateField('password', $value),
                name: 'password',
            )
            ->password(
                label: 'Confirm password',
                required: true,
                name: 'password_confirmation',
            )
            ->confirm(
                label: 'Mark the email address as verified?',
                default: true,
                name: 'verified',
            )
            ->submit();

        if ($responses['password'] !== $responses['password_confirmation']) {
            $this->components->error('The passwords do not match.');

            return self::FAILURE;
        }

        $user = spin(
            message: 'Creating user...',
            callback: fn () => User::create([
                'name'              => $responses['name'],
                'email'             => $responses['email'],
                'password'          => Hash::make($responses['password']),
                'email_verified_at' => $responses['verified'] ? now() : null,
            ]),
        );

        info("User \"{$user->name}\" created successfully.");

        table(
            headers: ['ID', 'Name', 'Email', 'Verified'],
            rows: [[
                (string) $user->id,
                $user->name,
                $user->email,
                $user->email_verified_at ? 'Yes' : 'No',
            ]],
        );

        return self::SUCCESS;
    }

    /**
     * Validate a single field and return the first error message, if any.
     */
    protected function validateField(string $field, string $value): ?string
    {
        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::default()],
        ];

        try {
            validator([$field => $value], [$field => $rules[$field]])->validate();
        } catch (ValidationException $e) {
            return $e->validator->errors()->first($field);
        }

        return null;
    }
}
