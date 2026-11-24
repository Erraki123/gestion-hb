@php
    $isAdmin = auth()->user()->isAdmin();
    $filters = [
        'q'             => (string) request('q', ''),
        'client'        => (string) request('client', ''),
        'status'        => (string) request('status', ''),
        'commercial_id' => (string) request('commercial_id', ''),
    ];
@endphp

<x-app-layout dark-header>
    <x-slot name="header">
        <div class="relative flex items-center justify-center text-center">
            <div>
                <h2 class="justify-center !text-lg !leading-tight">Commandes</h2>
                <p class="text-xs text-gray-500"
                   x-data="{ n: {{ $quotes->total() }} }" x-on:quotes-total.window="n = $event.detail">
                    <span x-text="n">{{ $quotes->total() }}</span> dossier(s) · devis, commande et livraison au même endroit
                </p>
            </div>
            <a href="{{ route('quotes.create') }}"
               class="absolute right-0 top-1/2 -translate-y-1/2 inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">
                <span class="text-base leading-none">+</span> Nouveau devis
            </a>
        </div>
    </x-slot>

    {{-- Affichage compact : header réduit + lignes du tableau plus serrées --}}
    <style>
        header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }

        .quotes-compact { padding-top: .75rem; padding-bottom: .75rem; }
        .quotes-compact table { font-size: .8rem; }
        .quotes-compact thead th { padding: .5rem .75rem; font-size: .68rem; }
        .quotes-compact thead tr.bg-gold-50 th { padding: .35rem .5rem; }
        .quotes-compact tbody td { padding: .4rem .75rem; }
        .quotes-compact tbody .text-xs { font-size: .68rem; line-height: 1rem; }
        .quotes-compact tbody td .flex { gap: .4rem; }
        .quotes-compact tbody td .flex a,
        .quotes-compact tbody td .flex button { padding: .2rem .55rem; font-size: .7rem; }
        .quotes-compact tbody td .flex a.act { padding: .2rem .45rem; }
    </style>

    <div class="quotes-compact max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- HNB_QUOTE_LEARNING_7 --}}
        @if ($isAdmin)
            <div class="mb-3 flex justify-end">
                <a href="{{ route('admin.quote-learning.index') }}" class="rounded-lg border border-gold-300 px-3 py-2 text-xs font-semibold">Apprentissage commercial</a>
            </div>
        @endif
        @if (session('success'))
            <div class="mb-3 rounded-xl border border-green-200 bg-green-100 p-2.5 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-3 rounded-xl border border-red-200 bg-red-100 p-2.5 text-sm text-red-800">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <div x-data="quotesTable(@js([
                'url'     => route('quotes.index'),
                'filters' => $filters,
                'rows'    => view('quotes._rows', ['quotes' => $quotes])->render(),
                'links'   => (string) $quotes->links(),
                'total'   => $quotes->total(),
            ]))">

            <div class="panel overflow-hidden">
                <div class="h-0.5" :class="loading ? 'animate-pulse bg-gradient-to-r from-gold-300 via-gold-500 to-gold-300' : 'bg-transparent'"></div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left">
                            <tr>
                                <th class="p-4">N°</th>
                                <th class="p-4">Client</th>
                                <th class="p-4">Date</th>
                                <th class="p-4 text-right">Total TTC</th>
                                <th class="p-4">Étape</th>
                                @if ($isAdmin) <th class="p-4">Commercial</th> @endif
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                            {{-- Un filtre par colonne : mise à jour pendant la frappe --}}
                            <tr class="bg-gold-50">
                                <th class="p-2" style="border-bottom:1px solid #E8E2D2">
                                    <input type="search" x-model="f.q" @input="queue()" placeholder="N°…" aria-label="Filtrer par numéro"
                                           class="w-full py-1.5 text-xs font-normal normal-case tracking-normal">
                                </th>
                                <th class="p-2" style="border-bottom:1px solid #E8E2D2">
                                    <input type="search" x-model="f.client" @input="queue()" placeholder="Nom du client…" aria-label="Filtrer par client"
                                           class="w-full py-1.5 text-xs font-normal normal-case tracking-normal">
                                </th>
                                <th class="p-2" style="border-bottom:1px solid #E8E2D2"></th>
                                <th class="p-2" style="border-bottom:1px solid #E8E2D2"></th>
                                <th class="p-2" style="border-bottom:1px solid #E8E2D2">
                                    <select x-model="f.status" @change="load(1)" aria-label="Filtrer par étape"
                                            class="w-full py-1.5 text-xs font-normal normal-case tracking-normal">
                                        <option value="">Toutes</option>
                                        @foreach (['devis' => 'Devis', 'commande' => 'Commande en cours', 'facture' => 'Facturé', 'refuse' => 'Refusé', 'expire' => 'Expiré'] as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </th>
                                @if ($isAdmin)
                                    <th class="p-2" style="border-bottom:1px solid #E8E2D2">
                                        <select x-model="f.commercial_id" @change="load(1)" aria-label="Filtrer par commercial"
                                                class="w-full py-1.5 text-xs font-normal normal-case tracking-normal">
                                            <option value="">Tous</option>
                                            @foreach ($commerciaux as $c)
                                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                    </th>
                                @endif
                                <th class="p-2 text-right" style="border-bottom:1px solid #E8E2D2">
                                    <button type="button" x-show="active" x-cloak @click="reset()"
                                            class="text-xs font-semibold normal-case tracking-normal text-gray-900 underline decoration-gold-500 decoration-2 underline-offset-4">
                                        Réinitialiser
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody x-html="rows" :class="loading ? 'opacity-50' : ''" class="transition-opacity"></tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3" x-html="links" @click="paginate($event)"></div>
        </div>
    </div>

    @include('quotes._action')

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('quotesTable', (init) => ({
                f: init.filters, rows: init.rows, links: init.links, total: init.total,
                loading: false, timer: null, ctrl: null,

                get active() { return Object.values(this.f).some(v => v !== '' && v !== null); },
                queue() { clearTimeout(this.timer); this.timer = setTimeout(() => this.load(1), 300); },
                reset() { Object.keys(this.f).forEach(k => this.f[k] = ''); this.load(1); },

                query(page) {
                    const p = new URLSearchParams();
                    Object.entries(this.f).forEach(([k, v]) => { if (v) p.set(k, v); });
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
                        window.dispatchEvent(new CustomEvent('quotes-total', { detail: d.total }));
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