<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2>Retours clients</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $returns->total() }} retour(s) au total</p>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <form method="GET" class="panel mb-5 flex flex-wrap gap-3 p-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="N° ou client..." class="w-full sm:w-64">
            <select name="status" class="w-full sm:w-auto">
                <option value="">Tous les statuts</option>
                @foreach (['brouillon', 'valide'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button class="act">Filtrer</button>
        </form>

        <div class="panel overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="p-4">N° retour</th>
                        <th class="p-4">Facture</th>
                        <th class="p-4">Client</th>
                        <th class="p-4">Date</th>
                        <th class="p-4">Statut</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($returns as $r)
                    <tr>
                        <td class="p-4 font-medium text-gray-900">{{ $r->displayNumber() }}</td>
                        <td class="p-4">{{ $r->invoice?->displayNumber() }}</td>
                        <td class="p-4">{{ $r->client?->name }}</td>
                        <td class="p-4">{{ optional($r->return_date)->format('d/m/Y') ?? '—' }}</td>
                        <td class="p-4"><x-status-badge :status="$r->status" /></td>
                        <td class="p-4">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('returns.show', $r) }}" class="act">Voir</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-10 text-center text-gray-500">Aucun retour client.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $returns->links() }}</div>
    </div>
</x-app-layout>