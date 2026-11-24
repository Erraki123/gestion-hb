@php $lbl = 'mb-1 block text-xs font-medium text-gray-600'; @endphp
<x-app-layout dark-header>
    <x-slot name="header">
        <x-purchase-head title="Modifier la commande" :sub="($purchaseOrder->number ?: 'Brouillon #' . $purchaseOrder->id) . ' · ' . $purchaseOrder->supplier?->name"
            :back="route('purchase-orders.show', $purchaseOrder)" back-label="Commande" />
    </x-slot>

    <div class="py-5 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <x-purchase-flash />
        <form method="POST" action="{{ route('purchase-orders.update', $purchaseOrder) }}" class="panel space-y-3 p-4">
            @csrf @method('PUT')
            <p class="text-xs text-gray-500">Les produits ne se modifient pas ici : pour changer les produits, supprimez le brouillon et recréez la commande.</p>
            <div>
                <label class="{{ $lbl }}">Livraison prévue</label>
                <input type="date" name="expected_date" value="{{ old('expected_date', $purchaseOrder->expected_date?->format('Y-m-d')) }}" class="block w-full sm:w-64">
            </div>
            <div>
                <label class="{{ $lbl }}">Notes</label>
                <textarea name="notes" rows="3" class="block w-full">{{ old('notes', $purchaseOrder->notes) }}</textarea>
            </div>
            <div class="flex justify-end">
                <button class="inline-flex items-center justify-center rounded-lg bg-gold-500 px-5 py-2 text-sm font-semibold text-gray-950 hover:bg-gold-400">Enregistrer</button>
            </div>
        </form>
    </div>
</x-app-layout>
