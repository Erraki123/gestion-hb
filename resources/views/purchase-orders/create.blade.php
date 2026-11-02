<x-app-layout>
    <x-slot name="header"><h2>Nouvelle commande fournisseur</h2></x-slot>
    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('purchase-orders.store') }}" class="panel p-6 space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="supplier_id" value="Fournisseur *" />
                    <select id="supplier_id" name="supplier_id" class="block w-full border-gray-300 rounded" required>
                        <option value="">Sélectionner</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="order_date" value="Date" />
                    <x-text-input id="order_date" name="order_date" type="date" class="block w-full" :value="old('order_date', today()->toDateString())" />
                </div>
            </div>
            <div class="rounded border border-dashed p-4">
                <div class="mb-2 font-semibold">Lignes</div>
                <div class="space-y-3">
                    <div class="grid grid-cols-5 gap-2 text-xs uppercase tracking-wider text-gray-500">
                        <span>Produit</span>
                        <span>Qté</span>
                        <span>PU HT</span>
                        <span>TVA</span>
                        <span>Remise</span>
                    </div>
                    <div class="grid grid-cols-5 gap-2">
                        <input type="text" name="items[0][product_id]" placeholder="ID produit" class="rounded border-gray-300" />
                        <input type="number" step="0.01" name="items[0][quantity]" class="rounded border-gray-300" value="1" />
                        <input type="number" step="0.01" name="items[0][unit_cost]" class="rounded border-gray-300" value="0" />
                        <input type="number" step="0.01" name="items[0][tax_rate]" class="rounded border-gray-300" value="20" />
                        <input type="number" step="0.01" name="items[0][discount_percent]" class="rounded border-gray-300" value="0" />
                    </div>
                </div>
            </div>
            <div class="flex justify-end"><button type="submit" class="px-3 py-2 bg-gold-500 text-gray-900 rounded">Créer</button></div>
        </form>
    </div>
</x-app-layout>
