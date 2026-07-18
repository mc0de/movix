<x-layouts::auth.cinema :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <x-cinema.input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="flex flex-col">
                <div class="mb-[9px] flex items-baseline justify-between gap-3">
                    <span class="cinema-label">{{ __('Password') }}</span>

                    @if (Route::has('password.request'))
                        <x-cinema.link class="cinema-forgot" :href="route('password.request')" wire:navigate>
                            {{ __('Forgot your password?') }}
                        </x-cinema.link>
                    @endif
                </div>

                <x-cinema.input
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />
            </div>

            <!-- Remember Me -->
            <x-cinema.checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <x-cinema.button type="submit" class="w-full" data-test="login-button">
                    {{ __('Log in') }}
                </x-cinema.button>
            </div>
        </form>

    </div>
</x-layouts::auth.cinema>
