<x-app-layout>
    <x-slot name="header">
        <h2>Commandes</h2>
        <p class="mt-1 text-sm text-gray-500">{{ $orders->total() }} commande(s)</p>
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
                @foreach (['nouvelle', 'confirmee', 'terminee', 'annulee'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Filtrer</button>
        </form>

        <div class="panel overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr><th class="p-4">N°</th><th class="p-4">Client</th><th class="p-4">Date</th><th class="p-4">Total TTC</th><th class="p-4">Statut</th><th class="p-4 text-right">Actions</th></tr>
                </thead>
                <tbody>
                @forelse ($orders as $o)
                    <tr>
                        <td class="p-4 font-semibold text-gray-900">{{ $o->number }}</td>
                        <td class="p-4">{{ $o->client?->name }}</td>
                        <td class="p-4 text-gray-600">{{ $o->created_at->format('d/m/Y') }}</td>
                        <td class="p-4 font-medium text-gray-900">{{ number_format($o->total_ttc, 2, ',', ' ') }} DH</td>
                        <td class="p-4"><x-status-badge :status="$o->status" /></td>
                        <td class="p-4"><div class="flex justify-end"><a href="{{ route('orders.show', $o) }}" class="act">Voir</a></div></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-12 text-center">
                            <x-icon name="cart" class="mx-auto h-10 w-10 text-gray-400" />
                            <p class="mt-3 font-medium text-gray-900">Aucune commande</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $orders->links() }}</div>
    </div>
</x-app-layout>