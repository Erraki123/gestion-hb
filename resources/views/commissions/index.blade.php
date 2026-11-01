<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2>Commissions</h2>
                <p class="mt-1 text-sm text-gray-500">Suivi des commissions calculées et versées</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('commissions.payouts') }}" class="px-4 py-2.5 bg-gray-700 text-white rounded">Versements</a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('commission-rules.index') }}" class="px-4 py-2.5 bg-indigo-600 text-white rounded">Règles</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-stat-card label="En attente de versement" :value="number_format($stats['pending'], 2, ',', ' ') . ' DH'" icon="clock" tone="yellow" />
            <x-stat-card label="Déjà versé" :value="number_format($stats['paid'], 2, ',', ' ') . ' DH'" icon="wallet" tone="green" />
            <x-stat-card label="Total calculé" :value="number_format($stats['total'], 2, ',', ' ') . ' DH'" icon="percent" />
        </div>

        @if (auth()->user()->isAdmin() && $summary->isNotEmpty())
            <div class="panel overflow-hidden">
                <h3 class="panel-title p-5 pb-3"><span class="ico"><x-icon name="briefcase" class="h-4 w-4" /></span> À verser par commercial</h3>
                <div class="divide-y divide-white/5 px-5 pb-3">
                    @foreach ($summary as $s)
                        <div class="flex flex-wrap items-center gap-3 py-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full border border-gold-500/40 bg-gold-500/10 text-xs font-semibold text-gold-400">{{ mb_strtoupper(mb_substr($s->user?->name ?? '?', 0, 2)) }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-gray-900">{{ $s->user?->name }}</div>
                                <div class="text-[11px] text-gray-500">{{ $s->n }} ligne(s)</div>
                            </div>
                            <span class="font-bold text-gold-400">{{ number_format($s->total, 2, ',', ' ') }} DH</span>
                            <a href="{{ route('commissions.payouts.create', ['user_id' => $s->user_id]) }}" class="act">Verser</a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <form method="GET" class="panel flex flex-wrap items-center gap-3 p-3">
            @if (auth()->user()->isAdmin())
                <select name="commercial_id">
                    <option value="">Tous les commerciaux</option>
                    @foreach ($commerciaux as $c)
                        <option value="{{ $c->id }}" @selected(request('commercial_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            @endif
            <select name="status">
                <option value="">Tous les statuts</option>
                <option value="en_attente" @selected(request('status') === 'en_attente')>En attente</option>
                <option value="versee" @selected(request('status') === 'versee')>Versée</option>
                <option value="annulee" @selected(request('status') === 'annulee')>Annulée</option>
            </select>
            <input type="date" name="from" value="{{ request('from') }}">
            <input type="date" name="to" value="{{ request('to') }}">
            <button class="rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Filtrer</button>
        </form>

        <div class="panel overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="p-4">Date</th>
                        @if (auth()->user()->isAdmin()) <th class="p-4">Commercial</th> @endif
                        <th class="p-4">Opération</th><th class="p-4">Règle</th>
                        <th class="p-4 text-right">Base</th><th class="p-4 text-right">Commission</th>
                        <th class="p-4">Statut</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($commissions as $c)
                    <tr class="{{ $c->status === 'annulee' ? 'opacity-60' : '' }}">
                        <td class="p-4 text-gray-600">{{ $c->computed_at->format('d/m/Y') }}</td>
                        @if (auth()->user()->isAdmin()) <td class="p-4">{{ $c->user?->name }}</td> @endif
                        <td class="p-4">
                            <span class="font-medium text-gray-900">{{ $c->sourceLabel() }}</span>
                            @if ($c->note) <div class="text-xs text-gray-500">{{ $c->note }}</div> @endif
                        </td>
                        <td class="p-4">{{ $c->ruleLabel() }}</td>
                        <td class="p-4 text-right">{{ number_format($c->base_amount, 2, ',', ' ') }}</td>
                        <td class="p-4 text-right font-bold {{ $c->amount < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($c->amount, 2, ',', ' ') }}</td>
                        <td class="p-4">
                            <x-status-badge :status="$c->status" />
                            @if ($c->payout)
                                <a class="block text-xs text-indigo-600" href="{{ route('commissions.payouts.show', $c->payout) }}">{{ $c->payout->number }}</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-12 text-center">
                            <x-icon name="percent" class="mx-auto h-10 w-10 text-gray-400" />
                            <p class="mt-3 font-medium text-gray-900">Aucune commission</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $commissions->links() }}</div>
    </div>
</x-app-layout>