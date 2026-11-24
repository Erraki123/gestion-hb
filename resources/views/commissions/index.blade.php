<x-app-layout dark-header>
    <x-slot name="header">
        <div class="relative flex items-center justify-center text-center">
            <div>
                <h2 class="justify-center !text-lg !leading-tight">Commissions</h2>
                <p class="text-xs text-gray-500">Calculées et versées</p>
            </div>
        </div>
    </x-slot>

    <style>
        header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }
        .com-compact table { font-size: .8rem; }
        .com-compact thead th { padding: .5rem .75rem; font-size: .68rem; }
        .com-compact tbody td { padding: .4rem .75rem; }
    </style>

    <div class="py-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-3">
        @include('partials.commission-tabs')

        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        {{-- Chiffres clés --}}
        <div class="panel grid grid-cols-2 divide-x divide-gray-200 text-center">
            <div class="px-3 py-2.5">
                <div class="text-[11px] uppercase tracking-wide text-gray-500">À verser</div>
                <div class="mt-0.5 text-base font-semibold text-gray-900">{{ number_format($stats['pending'], 2, ',', ' ') }} DH</div>
            </div>
            <div class="px-3 py-2.5">
                <div class="text-[11px] uppercase tracking-wide text-gray-500">Déjà versé</div>
                <div class="mt-0.5 text-base font-semibold text-gray-900">{{ number_format($stats['paid'], 2, ',', ' ') }} DH</div>
            </div>
        </div>

        {{-- Filtres : appliqués automatiquement au changement --}}
        <form method="GET" class="panel flex flex-wrap items-center gap-2 p-2">
            @if (auth()->user()->isAdmin())
                <select name="commercial_id" onchange="this.form.submit()">
                    <option value="">Tous les commerciaux</option>
                    @foreach ($commerciaux as $c)
                        <option value="{{ $c->id }}" @selected(request('commercial_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            @endif
            <select name="status" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                <option value="en_attente" @selected(request('status') === 'en_attente')>En attente</option>
                <option value="versee" @selected(request('status') === 'versee')>Versée</option>
                <option value="annulee" @selected(request('status') === 'annulee')>Annulée</option>
            </select>
            <input type="date" name="from" value="{{ request('from') }}" onchange="this.form.submit()" title="Du">
            <input type="date" name="to" value="{{ request('to') }}" onchange="this.form.submit()" title="Au">
            @if (request()->hasAny(['commercial_id', 'status', 'from', 'to']))
                <a href="{{ route('commissions.index') }}" class="text-xs text-gray-500 hover:text-gray-900">✕ Effacer</a>
            @endif
        </form>

        {{-- Liste --}}
        <div class="panel com-compact overflow-x-auto">
            <table class="min-w-full">
                <thead class="text-left">
                    <tr>
                        <th>Date</th>
                        @if (auth()->user()->isAdmin()) <th>Commercial</th> @endif
                        <th>Opération</th>
                        <th class="text-right">Commission</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($commissions as $c)
                    <tr class="border-t border-gray-100 {{ $c->status === 'annulee' ? 'opacity-60' : '' }}">
                        <td class="whitespace-nowrap text-gray-600">{{ $c->computed_at->format('d/m/Y') }}</td>
                        @if (auth()->user()->isAdmin()) <td class="whitespace-nowrap">{{ $c->user?->name }}</td> @endif
                        <td>
                            <span class="font-medium text-gray-900">{{ $c->sourceLabel() }}</span>
                            @if ($c->note) <div class="text-xs text-gray-500">{{ $c->note }}</div> @endif
                        </td>
                        <td class="whitespace-nowrap text-right font-semibold {{ $c->amount < 0 ? 'text-red-600' : 'text-gray-900' }} {{ $c->status === 'annulee' ? 'line-through' : '' }}">
                            {{ number_format($c->amount, 2, ',', ' ') }}
                        </td>
                        <td class="whitespace-nowrap">
                            <x-status-badge :status="$c->status" />
                            @if ($c->payout)
                                <a class="ml-1 text-xs text-gray-500 hover:text-gray-900 hover:underline" href="{{ route('commissions.payouts.show', $c->payout) }}">{{ $c->payout->number }}</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isAdmin() ? 5 : 4 }}" class="p-10 text-center">
                            <p class="font-medium text-gray-900">Aucune commission</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $commissions->links() }}</div>
    </div>
</x-app-layout>
