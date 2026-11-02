<x-pdf-layout>
    <div class="text-center">
        <h1 class="text-2xl font-bold">BON DE COMMANDE FOURNISSEUR</h1>
    </div>
    <div class="mt-6">
        <div>Fournisseur : {{ $purchaseOrder->supplier?->name ?? '—' }}</div>
        <div>N° : {{ $purchaseOrder->displayNumber() }}</div>
        <div>Date : {{ $purchaseOrder->order_date?->format('d/m/Y') ?? '—' }}</div>
    </div>
    <table class="mt-6 w-full text-sm">
        <thead><tr><th class="border p-2">Produit</th><th class="border p-2">Qté</th><th class="border p-2">PU HT</th><th class="border p-2">Total</th></tr></thead>
        <tbody>
        @foreach ($purchaseOrder->items as $item)
            <tr>
                <td class="border p-2">{{ $item->name }}</td>
                <td class="border p-2">{{ number_format((float) $item->quantity, 2, ',', ' ') }}</td>
                <td class="border p-2">{{ number_format((float) $item->unit_cost, 4, ',', ' ') }}</td>
                <td class="border p-2">{{ number_format((float) $item->line_total_ht, 2, ',', ' ') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</x-pdf-layout>
