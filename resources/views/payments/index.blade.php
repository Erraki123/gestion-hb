@php
    $filters = [
        'q'      => (string) request('q', ''),
        'method' => (string) request('method', ''),
        'status' => (string) request('status', ''),
        'from'   => (string) request('from', ''),
        'to'     => (string) request('to', ''),
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="grid items-center gap-3 text-center lg:grid-cols-3">
            <div class="flex justify-center lg:justify-start">
                <a href="{{ route('invoices.index') }}"
                   class="inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">← Factures</a>
            </div>

            <div>
                <h2 class="justify-center !text-lg !leading-tight">Paiements</h2>
                <p class="text-xs text-gray-500"
                   x-data="{ n: {{ $payments->total() }} }" x-on:list-total.window="n = $event.detail">
                    <span x-text="n">{{ $payments->total() }}</span> résultat(s)
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-3 text-xs lg:justify-end">
                <a href="{{ route('receivables.index') }}" class="link">Créances</a>
                <a href="{{ route('returns.index') }}" class="link">Retours</a>
            </div>
        </div>
    </x-slot>

    <div class="list-compact max-w-6xl mx-auto px-4 py-3 sm:px-6 lg:px-8 space-y-3">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-2.5 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-100 p-2.5 text-sm text-red-800">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <p class="text-sm text-gray-600">
            Aujourd'hui <strong class="text-green-700">{{ number_format($stats['today'], 2, ',', ' ') }}</strong>
            · Ce mois <strong class="text-green-700">{{ number_format($stats['month'], 2, ',', ' ') }}</strong>
            @if ($stats['cheques'] > 0)
                · Chèques en attente <strong class="text-yellow-700">{{ number_format($stats['cheques'], 2, ',', ' ') }}</strong>
                <span class="text-gray-500">({{ $stats['cheques_count'] }})</span>
            @endif
            DH
        </p>

        <div x-data="listTable(@js([
                'url'       => route('payments.index'),
                'exportUrl' => route('payments.export'),
                'filters'   => $filters,
                'rows'      => view('payments._rows', ['payments' => $payments])->render(),
                'links'     => (string) $payments->links(),
                'total'     => $payments->total(),
            ]))" class="space-y-3">

            {{-- UNE seule section de filtres : 5 filtres utiles --}}
            <div class="panel p-3">
                <div class="flex flex-wrap items-center gap-2">
                    <input type="search" x-model="f.q" @input="queue()" aria-label="Recherche"
                           placeholder="🔎 N°, client, facture, chèque, montant…" class="min-w-[14rem] flex-1 py-1.5 text-xs">

                    <select x-model="f.method" @change="load(1)" aria-label="Mode de paiement" class="py-1.5 text-xs">
                        <option value="">Mode : tous</option>
                        <option value="especes">Espèces</option>
                        <option value="cheque">Chèque</option>
                        <option value="virement">Virement</option>
                    </select>

                    <select x-model="f.status" @change="load(1)" aria-label="Statut" class="py-1.5 text-xs">
                        <option value="">Statut : tous</option>
                        <option value="valide">Valide</option>
                        <option value="cheque_en_attente">Chèque en attente</option>
                        <option value="annule">Annulé</option>
                    </select>

                    <label class="flex items-center gap-1 text-[11px] text-gray-500">Du
                        <input type="date" x-model="f.from" @change="load(1)" class="py-1 text-xs"></label>
                    <label class="flex items-center gap-1 text-[11px] text-gray-500">Au
                        <input type="date" x-model="f.to" @change="load(1)" class="py-1 text-xs"></label>

                    <button type="button" x-show="active" x-cloak @click="reset()"
                            class="text-xs font-semibold text-gray-900 underline decoration-gold-500 decoration-2 underline-offset-4">Réinitialiser</button>
                    <a :href="exportHref" target="_blank" class="act ml-auto">PDF global</a>
                </div>
            </div>

            <div class="panel overflow-hidden">
                <div class="h-0.5 transition-opacity" :class="loading ? 'animate-pulse bg-gradient-to-r from-gold-300 via-gold-500 to-gold-300' : 'bg-transparent'"></div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left">
                            <tr>
                                <th class="p-4">N°</th>
                                <th class="p-4">Date</th>
                                <th class="p-4">Client</th>
                                <th class="p-4">Facture</th>
                                <th class="p-4">Mode</th>
                                <th class="p-4 text-right">Montant</th>
                                <th class="p-4">Statut</th>
                                <th class="p-4"></th>
                            </tr>
                        </thead>
                        <tbody x-html="rows" :class="loading ? 'opacity-50' : ''" class="transition-opacity"></tbody>
                    </table>
                </div>
            </div>

            <div x-html="links" @click="paginate($event)"></div>
        </div>
    </div>

    @include('partials._list-table-script')
</x-app-layout>
