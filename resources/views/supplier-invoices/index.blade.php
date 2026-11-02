<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2>Factures fournisseur</h2>
                <p class="mt-1 text-sm text-gray-500">Suivi des dettes fournisseurs</p>
            </div>
            <a href="{{ route('supplier-invoices.create') }}" class="px-3 py-2 rounded bg-gold-500 text-gray-900">Nouvelle facture</a>
        </div>
    </x-slot>
    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left"><tr><th class="p-4">N°</th><th class="p-4">Fournisseur</th><th class="p-4">Date</th><th class="p-4">Total</th><th class="p-4">Status</th></tr></thead>
                    <tbody>
                    @foreach ($invoices as $invoice)
                        <tr>
                            <td class="p-4"><a href="{{ route('supplier-invoices.show', $invoice) }}" class="text-indigo-600">{{ $invoice->displayNumber() }}</a></td>
                            <td class="p-4">{{ $invoice->supplier?->name ?? '—' }}</td>
                            <td class="p-4">{{ $invoice->invoice_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="p-4">{{ number_format((float) $invoice->total_ttc, 2, ',', ' ') }} DH</td>
                            <td class="p-4"><x-status-badge :status="$invoice->status" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
