<x-guest-layout>
    <h2 class="text-2xl font-semibold text-gray-900">Connexion</h2>
    <p class="mb-6 mt-1 text-sm text-gray-500">Entrez vos identifiants pour accéder à votre espace.</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="Adresse e-mail" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Mot de passe" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between text-sm">
            <label class="inline-flex items-center gap-2 text-gray-600">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-gold-600 focus:ring-gold-500">
                Se souvenir de moi
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-gold-700 hover:underline">Mot de passe oublié ?</a>
            @endif
        </div>

        <x-primary-button class="w-full py-3">Se connecter</x-primary-button>
    </form>
</x-guest-layout>