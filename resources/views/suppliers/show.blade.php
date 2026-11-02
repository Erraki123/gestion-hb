<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2>{{ $supplier->name }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $supplier->company ?: 'Fournisseur' }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('suppliers.edit', $supplier) }}" class="px-3 py-2 rounded bg-gray-800 text-white">Modifier</a>
            </div>
        </div>
    </x-slot>
    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="panel p-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div><div class="text-[11px] uppercase tracking-wider text-gray-500">Email</div><div class="mt-1 text-sm font-medium">{{ $supplier->email ?: '—' }}</div></div>
            <div><div class="text-[11px] uppercase tracking-wider text-gray-500">Téléphone</div><div class="mt-1 text-sm font-medium">{{ $supplier->phone ?: '—' }}</div></div>
            <div><div class="text-[11px] uppercase tracking-wider text-gray-500">Délai</div><div class="mt-1 text-sm font-medium">{{ $supplier->payment_days }} jours</div></div>
        </div>
        <div class="panel p-6">
            <h3 class="mb-4 font-semibold">Commandes fournisseur</h3>
            @forelse ($supplier->purchaseOrders as $po)
                <div class="flex items-center justify-between border-b py-2">
                    <span>{{ $po->displayNumber() }}</span>
                    <x-status-badge :status="$po->status" />
                </div>
            @empty
                <div class="text-gray-500">Aucune commande.</div>
            @endforelse
        </div>
    </div>
</x-app-layout>
