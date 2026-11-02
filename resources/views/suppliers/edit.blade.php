<x-app-layout>
    <x-slot name="header"><h2>Modifier le fournisseur</h2></x-slot>
    <div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="panel p-6 space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><x-input-label for="name" value="Nom *" /><x-text-input id="name" name="name" class="block w-full" :value="old('name', $supplier->name)" required /></div>
                <div><x-input-label for="company" value="Société" /><x-text-input id="company" name="company" class="block w-full" :value="old('company', $supplier->company)" /></div>
                <div><x-input-label for="phone" value="Téléphone" /><x-text-input id="phone" name="phone" class="block w-full" :value="old('phone', $supplier->phone)" /></div>
                <div><x-input-label for="email" value="Email" /><x-text-input id="email" name="email" type="email" class="block w-full" :value="old('email', $supplier->email)" /></div>
                <div class="sm:col-span-2"><x-input-label for="address" value="Adresse" /><x-text-input id="address" name="address" class="block w-full" :value="old('address', $supplier->address)" /></div>
                <div><x-input-label for="city" value="Ville" /><x-text-input id="city" name="city" class="block w-full" :value="old('city', $supplier->city)" /></div>
                <div><x-input-label for="ice" value="ICE" /><x-text-input id="ice" name="ice" class="block w-full" :value="old('ice', $supplier->ice)" /></div>
                <div><x-input-label for="payment_days" value="Délai de paiement" /><x-text-input id="payment_days" name="payment_days" type="number" class="block w-full" :value="old('payment_days', $supplier->payment_days)" /></div>
                <div class="sm:col-span-2"><x-input-label for="notes" value="Notes" /><textarea id="notes" name="notes" rows="3" class="block w-full border-gray-300 rounded">{{ old('notes', $supplier->notes) }}</textarea></div>
            </div>
            <div class="flex justify-end"><button type="submit" class="px-3 py-2 bg-gold-500 text-gray-900 rounded">Enregistrer</button></div>
        </form>
    </div>
</x-app-layout>
