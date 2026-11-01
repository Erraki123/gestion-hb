<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Factures</h2></x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if (session('success'))
            <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded shadow">
                <div class="text-xs text-gray-500">Total facturé</div>
                <div class="text-lg font-bold">{{ number_format($stats['billed'], 2, ',', ' ') }} DH</div>
            </div>
            <div class="bg-white p-4 rounded shadow">
                <div class="text-xs text-gray-500">Encaissé</div>
                <div class="text-lg font-bold text-green-700">{{ number_format($stats['paid'], 2, ',', ' ') }} DH</div>
            </div>
            <div class="bg-white p-4 rounded shadow">
                <div class="text-xs text-gray-500">Reste à payer</div>
                <div class="text-lg font-bold text-orange-600">{{ number_format($stats['remaining'], 2, ',', ' ') }} DH</div>
            </div>
            <a href="{{ route('invoices.index', ['status' => 'en_retard']) }}" class="bg-red-50 p-4 rounded shadow">
                <div class="text-xs text-red-700">En retard</div>
                <div class="text-lg font-bold text-red-700">{{ number_format($stats['late'], 2, ',', ' ') }} DH</div>
            </a>
        </div>

        <form method="GET" class="flex flex-wrap gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="N° ou client..." class="border-gray-300 rounded w-full sm:w-64">
            <select name="status" class="border-gray-300 rounded">
                <option value="">Tous les statuts</option>
                @foreach (['brouillon' => 'Brouillon', 'emise' => 'Émise', 'partiellement_payee' => 'Partiellement payée', 'payee' => 'Payée', 'en_retard' => 'En retard', 'annulee' => 'Annulée'] as $v => $l)
                    <option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>
                @endforeach
            </select>
            <select name="type" class="border-gray-300 rounded">
                <option value="">Factures et avoirs</option>
                <option value="facture" @selected(request('type') === 'facture')>Factures</option>
                <option value="avoir" @selected(request('type') === 'avoir')>Avoirs</option>
            </select>
            <button class="px-4 py-2 bg-gray-800 text-white rounded">Filtrer</button>
        </form>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="p-3">N°</th><th class="p-3">Client</th><th class="p-3">Date</th>
                        <th class="p-3">Échéance</th><th class="p-3">Total TTC</th><th class="p-3">Reste</th>
                        <th class="p-3">Statut</th><th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($invoices as $inv)
                    <tr class="border-t">
                        <td class="p-3 font-medium">
                            {{ $inv->displayNumber() }}
                            @if ($inv->type === 'avoir') <span class="text-xs text-purple-600">(avoir)</span> @endif
                        </td>
                        <td class="p-3">{{ $inv->client?->name }}</td>
                        <td class="p-3">{{ $inv->issue_date->format('d/m/Y') }}</td>
                        <td class="p-3">{{ $inv->due_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="p-3">{{ number_format($inv->total_ttc, 2, ',', ' ') }}</td>
                        <td class="p-3">{{ $inv->type === 'facture' && $inv->status !== 'brouillon' ? number_format($inv->balance(), 2, ',', ' ') : '—' }}</td>
                        <td class="p-3"><x-status-badge :status="$inv->displayStatus()" /></td>
                        <td class="p-3 flex gap-3">
                            <a href="{{ route('invoices.show', $inv) }}" class="text-indigo-600">Voir</a>
                            <a href="{{ route('invoices.pdf', $inv) }}" target="_blank" class="text-gray-600">PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-4 text-center text-gray-500">Aucune facture.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $invoices->links() }}</div>
    </div>
</x-app-layout>