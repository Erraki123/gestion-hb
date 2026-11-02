<x-app-layout>
    <x-slot name="header"><h2>Modifier la commande</h2></x-slot>
    <div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('purchase-orders.update', $purchaseOrder) }}" class="panel p-6 space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><x-input-label for="expected_date" value="Date prévue" /><x-text-input id="expected_date" name="expected_date" type="date" class="block w-full" :value="old('expected_date', optional($purchaseOrder->expected_date)->format('Y-m-d'))" /></div>
                <div class="sm:col-span-2"><x-input-label for="notes" value="Notes" /><textarea id="notes" name="notes" rows="3" class="block w-full border-gray-300 rounded">{{ old('notes', $purchaseOrder->notes) }}</textarea></div>
            </div>
            <div class="flex justify-end"><button type="submit" class="px-3 py-2 bg-gold-500 text-gray-900 rounded">Enregistrer</button></div>
        </form>
    </div>
</x-app-layout>
