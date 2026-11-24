@php
    $isAdmin = auth()->user()->isAdmin();
    $tabs = [
        'tous'        => 'Toutes',
        'a_encaisser' => 'À encaisser',
        'en_retard'   => 'En retard',
        'payees'      => 'Payées',
        'avoirs'      => 'Avoirs',
    ];
    $filters = [
        'tab'           => (string) $tab,
        'q'             => (string) request('q', ''),
        'from'          => (string) request('from', ''),
        'to'            => (string) request('to', ''),
        'commercial_id' => (string) request('commercial_id', ''),
    ];
@endphp
<x-app-layout dark-header>
    <x-slot name="header">
        <div class="grid items-center gap-3 text-center lg:grid-cols-3">
            <div class="hidden lg:block"></div>

            <div>
                <h2 class="justify-center !text-lg !leading-tight">Factures</h2>
                <p class="text-xs text-gray-500"
                   x-data="{ n: {{ $invoices->total() }} }" x-on:invoices-total.window="n = $event.detail">
                    <span x-text="n">{{ $invoices->total() }}</span> résultat(s)
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-3 text-xs lg:justify-end">
                <a href="{{ route('receivables.index') }}" class="text-gray-300 hover:text-gold-300">Créances</a>
                <a href="{{ route('payments.index') }}" class="text-gray-300 hover:text-gold-300">Paiements</a>
                <a href="{{ route('returns.index') }}" class="text-gray-300 hover:text-gold-300">Retours</a>
                <a href="{{ route('invoices.create') }}"
                   class="inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">
                    <span class="text-base leading-none">+</span> Nouvelle facture
                </a>
            </div>
        </div>
    </x-slot>

    <style>
        header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }
        .inv-compact table { font-size: .8rem; }
        .inv-compact thead th { padding: .5rem .75rem; font-size: .68rem; }
        .inv-compact tbody td { padding: .4rem .75rem; }
        .inv-compact tbody .text-xs { font-size: .68rem; line-height: 1rem; }
        .inv-compact tbody td .flex { gap: .4rem; }
        .inv-compact tbody td .flex a, .inv-compact tbody td .flex button { padding: .2rem .55rem; font-size: .7rem; }
    </style>

    <div class="inv-compact max-w-7xl mx-auto px-4 py-3 sm:px-6 lg:px-8 space-y-3">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-2.5 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-100 p-2.5 text-sm text-red-800">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        {{-- Les chiffres : une seule ligne --}}
        <p class="text-sm text-gray-600">
            Facturé <strong class="text-gray-900">{{ number_format($stats['billed'], 2, ',', ' ') }}</strong>
            · Encaissé <strong class="text-green-700">{{ number_format($stats['cashed'], 2, ',', ' ') }}</strong>
            @if ($stats['cheques'] > 0)
                · Chèques en attente <strong class="text-yellow-700">{{ number_format($stats['cheques'], 2, ',', ' ') }}</strong>
            @endif
            · Reste <strong class="text-orange-600">{{ number_format($stats['remaining'], 2, ',', ' ') }}</strong>
            @if ($stats['late'] > 0)
                · En retard <strong class="text-red-700">{{ number_format($stats['late'], 2, ',', ' ') }}</strong>
            @endif
            DH
        </p>

        <div x-data="invoicesTable(@js([
                'url'     => route('invoices.index'),
                'filters' => $filters,
                'rows'    => view('invoices._rows', ['invoices' => $invoices])->render(),
                'links'   => (string) $invoices->links(),
                'total'   => $invoices->total(),
            ]))" class="space-y-3">

            {{-- UNE seule section de filtres : onglets + recherche + période (+ commercial pour l'admin) --}}
            <div class="panel space-y-3 p-3">
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($tabs as $key => $label)
                        <button type="button" @click="setTab('{{ $key }}')"
                                :class="f.tab === '{{ $key }}' ? 'rounded-lg bg-gold-500 px-3 py-1.5 text-xs font-semibold text-gray-950' : 'act'">
                            {{ $label }}@if (($counts[$key] ?? 0) > 0)<span class="ml-1 opacity-70">{{ $counts[$key] }}</span>@endif
                        </button>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center gap-2 border-t border-gold-200 pt-3">
                    <input type="search" x-model="f.q" @input="queue()" aria-label="Recherche"
                           placeholder="🔎 N° facture, client, chèque, montant…" class="min-w-[14rem] flex-1 py-1.5 text-xs">

                    @if ($isAdmin)
                        <select x-model="f.commercial_id" @change="load(1)" aria-label="Commercial" class="py-1.5 text-xs">
                            <option value="">Commercial : tous</option>
                            @foreach ($commerciaux as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    @endif

                    <label class="flex items-center gap-1 text-[11px] text-gray-500">Du
                        <input type="date" x-model="f.from" @change="load(1)" class="py-1 text-xs"></label>
                    <label class="flex items-center gap-1 text-[11px] text-gray-500">Au
                        <input type="date" x-model="f.to" @change="load(1)" class="py-1 text-xs"></label>

                    <button type="button" x-show="active" x-cloak @click="reset()"
                            class="text-xs font-semibold text-gray-900 underline decoration-gold-500 decoration-2 underline-offset-4">Réinitialiser</button>
                </div>
            </div>

            <div class="panel overflow-hidden">
                <div class="h-0.5 transition-opacity" :class="loading ? 'animate-pulse bg-gradient-to-r from-gold-300 via-gold-500 to-gold-300' : 'bg-transparent'"></div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left">
                            <tr>
                                <th class="p-4">Facture</th>
                                <th class="p-4">Client</th>
                                <th class="p-4">Échéance</th>
                                <th class="p-4 text-right">Reste</th>
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

    @include('invoices._confirm')

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('invoicesTable', (init) => ({
                f: init.filters, rows: init.rows, links: init.links, total: init.total,
                loading: false, timer: null, ctrl: null,

                get active() {
                    return Object.entries(this.f).some(([k, v]) => k === 'tab' ? v !== 'tous' : (v !== '' && v !== null));
                },
                queue() { clearTimeout(this.timer); this.timer = setTimeout(() => this.load(1), 300); },
                setTab(t) { this.f.tab = t; this.load(1); },
                reset() {
                    Object.keys(this.f).forEach(k => this.f[k] = '');
                    this.f.tab = 'tous';
                    this.load(1);
                },

                query(page) {
                    const p = new URLSearchParams();
                    Object.entries(this.f).forEach(([k, v]) => {
                        if (v && !(k === 'tab' && v === 'tous')) p.set(k, v);
                    });
                    if (page > 1) p.set('page', page);
                    const s = p.toString();
                    return s ? '?' + s : '';
                },

                async load(page = 1) {
                    if (this.ctrl) this.ctrl.abort();
                    const mine = this.ctrl = new AbortController();
                    this.loading = true;
                    const qs = this.query(page);
                    try {
                        const r = await fetch(init.url + qs, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            signal: mine.signal,
                        });
                        if (r.status === 401 || r.status === 419) { location.reload(); return; }
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        const d = await r.json();
                        this.rows = d.rows; this.links = d.links; this.total = d.total;
                        window.dispatchEvent(new CustomEvent('invoices-total', { detail: d.total }));
                        history.replaceState(null, '', init.url + qs);
                    } catch (e) {
                        if (e.name !== 'AbortError') console.error(e);
                    } finally {
                        if (this.ctrl === mine) this.loading = false;
                    }
                },

                paginate(e) {
                    const a = e.target.closest('a[href]');
                    if (!a) return;
                    e.preventDefault();
                    this.load(parseInt(new URL(a.href, location.origin).searchParams.get('page') || '1', 10));
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },
            }));
        });
    </script>
</x-app-layout>
