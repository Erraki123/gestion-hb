<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2>{{ $purchaseOrder->displayNumber() }} <x-status-badge :status="$purchaseOrder->status" /></h2>
                <p class="mt-1 text-sm text-gray-500">Fournisseur : {{ $purchaseOrder->supplier?->name ?? '—' }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('purchase-orders.pdf', $purchaseOrder) }}" class="px-3 py-2 rounded bg-gray-800 text-white">PDF</a>
                @if ($purchaseOrder->isDraft())
                    <form method="POST" action="{{ route('purchase-orders.confirm', $purchaseOrder) }}" onsubmit="return confirm('Confirmer cette commande ?')">
                        @csrf
                        <button class="px-3 py-2 rounded bg-green-600 text-white">Confirmer</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>
    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="panel p-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div><div class="text-[11px] uppercase tracking-wider text-gray-500">Date</div><div class="mt-1 text-sm font-medium">{{ $purchaseOrder->order_date?->format('d/m/Y') ?? '—' }}</div></div>
                <div><div class="text-[11px] uppercase tracking-wider text-gray-500">Date prévue</div><div class="mt-1 text-sm font-medium">{{ $purchaseOrder->expected_date?->format('d/m/Y') ?? '—' }}</div></div>
                <div><div class="text-[11px] uppercase tracking-wider text-gray-500">Total TTC</div><div class="mt-1 text-sm font-medium">{{ number_format((float) $purchaseOrder->total_ttc, 2, ',', ' ') }} DH</div></div>
            </div>
        </div>
        <div class="panel p-6">
            <h3 class="mb-4 font-semibold">Lignes</h3>
            <table class="min-w-full text-sm">
                <thead class="text-left"><tr><th class="p-3">Produit</th><th class="p-3">Qté</th><th class="p-3">Reçue</th><th class="p-3">PU HT</th><th class="p-3">Total</th></tr></thead>
                <tbody>
                @foreach ($purchaseOrder->items as $item)
                    <tr>
                        <td class="p-3">{{ $item->name }}</td>
                        <td class="p-3">{{ number_format((float) $item->quantity, 2, ',', ' ') }}</td>
                        <td class="p-3">{{ number_format((float) $item->received_quantity, 2, ',', ' ') }}</td>
                        <td class="p-3">{{ number_format((float) $item->unit_cost, 4, ',', ' ') }}</td>
                        <td class="p-3">{{ number_format((float) $item->line_total_ht, 2, ',', ' ') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
