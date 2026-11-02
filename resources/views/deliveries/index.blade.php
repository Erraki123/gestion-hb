<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2>Livraisons</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $deliveries->total() }} bon(s)</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <form method="GET" class="panel mb-5 flex flex-wrap items-center gap-3 p-3">
            <div class="relative w-full sm:w-72">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" />
                <input type="text" name="q" value="{{ request('q') }}" placeholder="N° ou client…" class="w-full pl-10">
            </div>
            <select name="status">
                <option value="">Tous les statuts</option>
                @foreach (['brouillon', 'valide', 'annule'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Filtrer</button>
        </form>

        <div class="panel overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="p-4">N°</th>
                        <th class="p-4">Commande</th>
                        <th class="p-4">Client</th>
                        <th class="p-4">Date</th>
                        <th class="p-4">Statut</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($deliveries as $d)
                    <tr>
                        <td class="p-4 font-semibold text-gray-900">{{ $d->displayNumber() }}</td>
                        <td class="p-4">{{ $d->order?->number ?? '—' }}</td>
                        <td class="p-4">{{ $d->client?->name ?? '—' }}</td>
                        <td class="p-4 text-gray-600">{{ $d->delivery_date ? $d->delivery_date->format('d/m/Y') : '—' }}</td>
                        <td class="p-4"><x-status-badge :status="$d->status" /></td>
                        <td class="p-4">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('deliveries.show', $d) }}" class="act">Voir</a>
                                <a href="{{ route('deliveries.pdf', $d) }}" target="_blank" class="act">PDF</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-12 text-center">
                            <x-icon name="truck" class="mx-auto h-10 w-10 text-gray-400" />
                            <p class="mt-3 font-medium text-gray-900">Aucune livraison</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $deliveries->links() }}</div>
    </div>
</x-app-layout>
