@csrf
<div class="space-y-5">
    <div class="grid gap-5 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <x-input-label for="name" value="Nom complet" />
            <x-text-input id="name" name="name" class="mt-1 block w-full"
                :value="old('name', $commercial->name ?? '')" required />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                :value="old('email', $commercial->email ?? '')" required />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="phone" value="Téléphone" />
            <x-text-input id="phone" name="phone" class="mt-1 block w-full"
                :value="old('phone', $commercial->phone ?? '')" />
            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="password" value="Mot de passe {{ isset($commercial) ? '(laisser vide pour ne pas changer)' : '' }}" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                :required="! isset($commercial)" />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="password_confirmation" value="Confirmer le mot de passe" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" />
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 border-t border-white/5 pt-5">
        <a href="{{ route('admin.commerciaux.index') }}" class="text-sm text-gray-500 hover:text-white">Annuler</a>
        <x-primary-button>Enregistrer</x-primary-button>
    </div>
</div>
