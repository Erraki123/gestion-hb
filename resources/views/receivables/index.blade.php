<x-app-layout>
    <x-slot name="header">
        <h2>Créances</h2>
        <p class="mt-1 text-sm text-gray-500">Reste à encaisser, par ancienneté</p>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        @if (auth()->user()->isAdmin())
            <form method="GET" class="panel flex flex-wrap items-center gap-3 p-3">
                <select name="commercial_id">
                    <option value="">Tous les commerciaux</option>
                    @foreach ($commerciaux as $c)
                        <option value="{{ $c->id }}" @selected(request('commercial_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
                <button class="rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Filtrer</button>
            </form>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <x-stat-card label="Total à encaisser" :value="number_format($totals['total'], 2, ',', ' ') . ' DH'" icon="wallet" tone="indigo" />
            <x-stat-card label="Non échu" :value="number_format($totals['not_due'], 2, ',', ' ') . ' DH'" icon="clock" />
            <x-stat-card label="1–30 jours" :value="number_format($totals['d30'], 2, ',', ' ') . ' DH'" icon="clock" tone="yellow" />
            <x-stat-card label="31–60 jours" :value="number_format($totals['d60'], 2, ',', ' ') . ' DH'" icon="clock" tone="yellow" />
            <x-stat-card label="Plus de 60 jours" :value="number_format($totals['d60p'], 2, ',', ' ') . ' DH'" icon="activity" tone="red" />
        </div>

        <div class="panel overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="p-4">Client</th><th class="p-4">Factures</th>
                        <th class="p-4 text-right">Non échu</th><th class="p-4 text-right">1–30 j</th>
                        <th class="p-4 text-right">31–60 j</th><th class="p-4 text-right">+60 j</th>
                        <th class="p-4 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($rows as $r)
                    <tr>
                        <td class="p-4 font-semibold">
                            <a class="text-indigo-600" href="{{ route('clients.show', $r['client']) }}">{{ $r['client']->name }}</a>
                        </td>
                        <td class="p-4">
                            <a class="act" href="{{ route('invoices.index', ['q' => $r['client']->name]) }}">{{ $r['count'] }}</a>
                        </td>
                        <td class="p-4 text-right">{{ number_format($r['buckets']['not_due'], 2, ',', ' ') }}</td>
                        <td class="p-4 text-right">{{ number_format($r['buckets']['d30'], 2, ',', ' ') }}</td>
                        <td class="p-4 text-right">{{ number_format($r['buckets']['d60'], 2, ',', ' ') }}</td>
                        <td class="p-4 text-right text-red-600">{{ number_format($r['buckets']['d60p'], 2, ',', ' ') }}</td>
                        <td class="p-4 text-right font-bold text-gray-900">{{ number_format($r['total'], 2, ',', ' ') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-12 text-center">
                            <x-icon name="shield" class="mx-auto h-10 w-10 text-gold-500" />
                            <p class="mt-3 font-medium text-gray-900">Aucune créance 🎉</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>