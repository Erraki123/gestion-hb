<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2>{{ $receipt->displayNumber() }} <x-status-badge :status="$receipt->status" /></h2>
                <p class="mt-1 text-sm text-gray-500">Réception fournisseur</p>
            </div>
            @if ($receipt->isDraft())
                <form method="POST" action="{{ route('receipts.validate', $receipt) }}" onsubmit="return confirm('Valider cette réception ?')">
                    @csrf
                    <button class="px-3 py-2 rounded bg-green-600 text-white">Valider</button>
                </form>
            @endif
        </div>
    </x-slot>
    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="panel p-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div><div class="text-[11px] uppercase tracking-wider text-gray-500">Commande</div><div class="mt-1 text-sm font-medium">{{ $receipt->purchaseOrder?->displayNumber() ?? '—' }}</div></div>
                <div><div class="text-[11px] uppercase tracking-wider text-gray-500">Fournisseur</div><div class="mt-1 text-sm font-medium">{{ $receipt->supplier?->name ?? '—' }}</div></div>
                <div><div class="text-[11px] uppercase tracking-wider text-gray-500">Référence</div><div class="mt-1 text-sm font-medium">{{ $receipt->supplier_ref ?? '—' }}</div></div>
            </div>
        </div>
        <div class="panel p-6">
            <table class="min-w-full text-sm">
                <thead class="text-left"><tr><th class="p-3">Produit</th><th class="p-3">Qté</th><th class="p-3">PU net</th></tr></thead>
                <tbody>
                @foreach ($receipt->items as $item)
                    <tr>
                        <td class="p-3">{{ $item->name }}</td>
                        <td class="p-3">{{ number_format((float) $item->quantity, 2, ',', ' ') }}</td>
                        <td class="p-3">{{ number_format((float) $item->net_unit_cost, 4, ',', ' ') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
