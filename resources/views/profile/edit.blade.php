<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="p-7 space-y-5 max-w-3xl">
        <x-card padding="p-6 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </x-card>

        <x-card padding="p-6 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </x-card>
    </div>
</x-app-layout>
