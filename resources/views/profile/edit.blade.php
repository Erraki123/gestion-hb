<x-app-layout>
    <x-slot name="header">
        <h2>Mon profil</h2>
        <p class="mt-1 text-sm text-gray-500">Gérez vos informations et votre mot de passe</p>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="panel p-6 sm:p-8">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="panel p-6 sm:p-8">
            @include('profile.partials.update-password-form')
        </div>
    </div>
</x-app-layout>
