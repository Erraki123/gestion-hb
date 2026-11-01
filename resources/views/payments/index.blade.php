<x-app-layout>
    <x-slot name="header">
        <h2>Paiements</h2>
        <p class="mt-1 text-sm text-gray-500">{{ $payments->total() }} paiement(s)</p>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Encaissé aujourd'hui" :value="number_format($stats['today'], 2, ',', ' ') . ' DH'" icon="wallet" tone="green" />
            <x-stat-card label="Encaissé ce mois" :value="number_format($stats['month'], 2, ',', ' ') . ' DH'" icon="wallet" tone="green" />
            <x-stat-card label="Chèques en attente" :value="number_format($stats['cheques'], 2, ',', ' ') . ' DH'"
                :sub="$stats['cheques_count'] . ' chèque(s)'" icon="receipt" tone="yellow"
                :href="route('payments.index', ['cheque_status' => 'en_attente'])" />
            <x-stat-card label="Créances" value="Voir →" sub="Factures à encaisser" icon="clock" tone="red"
                :href="route('receivables.index')" />
        </div>

        <form method="GET" class="panel flex flex-wrap items-center gap-3 p-3">
            <div class="relative w-full sm:w-64">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" />
                <input type="text" name="q" value="{{ request('q') }}" placeholder="N°, client, chèque…" class="w-full pl-10">
            </div>
            <select name="method">
                <option value="">Tous les modes</option>
                @foreach (['especes' => 'Espèces', 'virement' => 'Virement', 'cheque' => 'Chèque'] as $v => $l)
                    <option value="{{ $v }}" @selected(request('method') === $v)>{{ $l }}</option>
                @endforeach
            </select>
            <select name="status">
                <option value="">Tous les statuts</option>
                <option value="valide" @selected(request('status') === 'valide')>Valide</option>
                <option value="annule" @selected(request('status') === 'annule')>Annulé</option>
            </select>
            <select name="cheque_status">
                <option value="">Chèques : tous</option>
                @foreach (['en_attente' => 'En attente', 'encaisse' => 'Encaissé', 'rejete' => 'Rejeté'] as $v => $l)
                    <option value="{{ $v }}" @selected(request('cheque_status') === $v)>{{ $l }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}">
            <input type="date" name="to" value="{{ request('to') }}">
            <button class="rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Filtrer</button>
        </form>

        <div class="panel overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="p-4">N°</th><th class="p-4">Date</th><th class="p-4">Client</th>
                        <th class="p-4">Facture</th><th class="p-4">Mode</th>
                        <th class="p-4 text-right">Montant</th><th class="p-4">Statut</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($payments as $p)
                    <tr class="{{ $p->status === 'annule' ? 'opacity-60' : '' }}">
                        <td class="p-4 font-semibold text-gray-900">{{ $p->number }}</td>
                        <td class="p-4 text-gray-600">{{ $p->paid_at->format('d/m/Y') }}</td>
                        <td class="p-4">{{ $p->client?->name }}</td>
                        <td class="p-4"><a class="text-indigo-600" href="{{ route('invoices.show', $p->invoice) }}">{{ $p->invoice->number }}</a></td>
                        <td class="p-4">
                            {{ $p->methodLabel() }}
                            @if ($p->cheque_status) <x-status-badge :status="$p->cheque_status" /> @endif
                        </td>
                        <td class="p-4 text-right font-semibold text-gray-900">{{ number_format($p->amount, 2, ',', ' ') }} DH</td>
                        <td class="p-4"><x-status-badge :status="$p->status" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-12 text-center">
                            <x-icon name="wallet" class="mx-auto h-10 w-10 text-gray-400" />
                            <p class="mt-3 font-medium text-gray-900">Aucun paiement</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $payments->links() }}</div>
    </div>
</x-app-layout>