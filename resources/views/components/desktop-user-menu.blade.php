<div class="flex items-center gap-3">
    <div class="flex items-center gap-2">
        <x-user-avatar :name="auth()->user()->name" class="size-7" />
        <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
    </div>

    <flux:navbar class="space-x-0.5 rtl:space-x-reverse py-0!">
        <flux:tooltip :content="__('Settings')" position="bottom">
            <flux:navbar.item
                class="!h-10 [&>div>svg]:size-5"
                icon="cog"
                :href="route('profile.edit')"
                :current="request()->routeIs('profile.edit')"
                :label="__('Settings')"
                wire:navigate
            />
        </flux:tooltip>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <flux:tooltip :content="__('Log out')" position="bottom">
                <flux:navbar.item
                    as="button"
                    type="submit"
                    class="!h-10 [&>div>svg]:size-5 cursor-pointer"
                    icon="arrow-right-start-on-rectangle"
                    :label="__('Log out')"
                    data-test="logout-button"
                />
            </flux:tooltip>
        </form>
    </flux:navbar>
</div>
