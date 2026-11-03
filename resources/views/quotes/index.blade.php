<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2>Devis</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $quotes->total() }} devis</p>
            </div>
            <a href="{{ route('quotes.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-gold-500 px-4 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">
                <span class="text-lg leading-none">+</span> Nouveau devis
            </a>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        {{-- Filtres --}}
        <form method="GET" class="panel mb-5 flex flex-wrap items-center gap-3 p-3">
            <div class="relative w-full sm:w-72">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" />
                <input type="text" name="q" value="{{ request('q') }}" placeholder="N° ou client…" class="w-full pl-10">
            </div>
            <select name="status">
                <option value="">Tous les statuts</option>
                @foreach (['brouillon', 'envoye', 'accepte', 'refuse', 'expire'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Filtrer</button>
            @if (request()->hasAny(['q', 'status']))
                <a href="{{ route('quotes.index') }}" class="text-sm text-gray-500 hover:text-gray-900">Réinitialiser</a>
            @endif
        </form>

        {{-- Tableau --}}
        <div class="panel overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="p-4">N°</th>
                        <th class="p-4">Client</th>
                        <th class="p-4">Date</th>
                        <th class="p-4 text-right">Total TTC</th>
                        <th class="p-4">Statut</th>
                        @if (auth()->user()->isAdmin()) <th class="p-4">Commercial</th> @endif
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($quotes as $q)
                    <tr>
                        <td class="p-4">
                            <a href="{{ route('quotes.show', $q) }}" class="font-semibold text-gray-900 hover:text-gold-400">{{ $q->number }}</a>
                        </td>
                        <td class="p-4 text-gray-700">{{ $q->client?->name ?: '—' }}</td>
                        <td class="p-4 text-gray-700">{{ $q->issue_date->format('d/m/Y') }}</td>
                        <td class="p-4 text-right font-medium text-gray-900">{{ number_format($q->total_ttc, 2, ',', ' ') }} DH</td>
                        <td class="p-4"><x-status-badge :status="$q->status" /></td>
                        @if (auth()->user()->isAdmin()) <td class="p-4 text-gray-700">{{ $q->commercial?->name ?: '—' }}</td> @endif
                        <td class="p-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('quotes.show', $q) }}" class="act">Voir</a>
                                <a href="{{ route('quotes.pdf', $q) }}" target="_blank" class="act">PDF</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isAdmin() ? 7 : 6 }}" class="p-12 text-center">
                            <p class="font-medium text-gray-900">Aucun devis trouvé</p>
                            <p class="text-sm text-gray-500">Cliquez sur « Nouveau devis » pour commencer.</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">{{ $quotes->links() }}</div>
    </div>
</x-app-layout>
