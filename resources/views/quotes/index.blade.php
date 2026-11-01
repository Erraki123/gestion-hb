<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2>Devis</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $quotes->total() }} devis au total</p>
            </div>
            <a href="{{ route('quotes.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 font-medium text-white shadow-lg shadow-gold-900/20 transition hover:-translate-y-0.5">
                + Nouveau devis
            </a>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <form method="GET" class="panel mb-5 flex flex-wrap gap-3 p-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="N° ou client..." class="w-full sm:w-64">
            <select name="status" class="w-full sm:w-auto">
                <option value="">Tous les statuts</option>
                @foreach (['brouillon', 'envoye', 'accepte', 'refuse', 'expire'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button class="act">Filtrer</button>
        </form>

        <div class="panel overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="p-4">N°</th>
                        <th class="p-4">Client</th>
                        <th class="p-4">Date</th>
                        <th class="p-4">Total TTC</th>
                        <th class="p-4">Statut</th>
                        @if (auth()->user()->isAdmin()) <th class="p-4">Commercial</th> @endif
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($quotes as $q)
                    <tr>
                        <td class="p-4 font-medium text-gray-100">{{ $q->number }}</td>
                        <td class="p-4">{{ $q->client?->name }}</td>
                        <td class="p-4">{{ $q->issue_date->format('d/m/Y') }}</td>
                        <td class="p-4">{{ number_format($q->total_ttc, 2, ',', ' ') }} DH</td>
                        <td class="p-4"><x-status-badge :status="$q->status" /></td>
                        @if (auth()->user()->isAdmin()) <td class="p-4">{{ $q->commercial?->name }}</td> @endif
                        <td class="p-4">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('quotes.show', $q) }}" class="act">Voir</a>
                                <a href="{{ route('quotes.pdf', $q) }}" target="_blank" class="act">PDF</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-10 text-center text-gray-500">Aucun devis.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $quotes->links() }}</div>
    </div>
</x-app-layout>