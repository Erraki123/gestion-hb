@php
    $isAdmin = auth()->user()->isAdmin();
    $filters = [
        'name'          => (string) request('name', request('q', '')),
        'phone'         => (string) request('phone', ''),
        'city'          => (string) request('city', ''),
        'status'        => (string) request('status', ''),
        'commercial_id' => (string) request('commercial_id', ''),
    ];
@endphp

<x-app-layout dark-header>
    <x-slot name="header">
        <div class="relative flex items-center justify-center text-center">
            <div>
                <h2 class="justify-center !text-lg !leading-tight">Clients</h2>
                <p class="text-xs text-gray-500"
                   x-data="{ n: {{ $clients->total() }} }"
                   x-on:clients-total.window="n = $event.detail">
                    <span x-text="n">{{ $clients->total() }}</span> client(s)
                </p>
            </div>

            <a href="{{ route('clients.create') }}"
               class="absolute right-0 top-1/2 -translate-y-1/2 inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">
                <span class="text-base leading-none">+</span> Nouveau client
            </a>
        </div>
    </x-slot>

    {{-- Affichage compact : header réduit + lignes du tableau plus serrées --}}
    <style>
        header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }

        .clients-compact { padding-top: .75rem; padding-bottom: .75rem; }
        .clients-compact thead th { padding: .5rem .75rem; font-size: .68rem; }
        .clients-compact thead tr.bg-gold-50 th { padding: .35rem .5rem; }
        .clients-compact tbody td { padding: .4rem .75rem; }
        .clients-compact tbody td > div.flex { gap: .6rem; }
        .clients-compact tbody td span.h-10 { height: 2rem; width: 2rem; font-size: .7rem; }
        .clients-compact tbody .act { padding: .2rem .55rem; font-size: .7rem; }
        .clients-compact tbody .text-xs { font-size: .68rem; line-height: 1rem; }
        .clients-compact table { font-size: .8rem; }
    </style>

    <div class="clients-compact max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-3 rounded-xl border border-green-200 bg-green-100 p-2.5 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <div x-data="clientsTable(@js([
                'url'     => route('clients.index'),
                'filters' => $filters,
                'rows'    => view('clients._rows', ['clients' => $clients])->render(),
                'links'   => (string) $clients->links(),
                'total'   => $clients->total(),
            ]))">

            <div class="panel overflow-hidden">
                {{-- Barre de chargement --}}
                <div class="h-0.5 transition-opacity" :class="loading ? 'animate-pulse bg-gradient-to-r from-gold-300 via-gold-500 to-gold-300' : 'bg-transparent'"></div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left">
                            <tr>
                                <th class="p-4">Client</th>
                                <th class="p-4">Téléphone</th>
                                <th class="p-4">Ville</th>
                                <th class="p-4">Statut</th>
                                @if ($isAdmin) <th class="p-4">Commercial</th> @endif
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                            {{-- Un filtre par colonne : la liste se met à jour pendant la frappe --}}
                            <tr class="bg-gold-50">
                                <th class="p-2" style="border-bottom:1px solid #E8E2D2">
                                    <input type="search" x-model="f.name" @input="queue()" placeholder="Nom, société, e-mail…"
                                           aria-label="Filtrer par nom" class="w-full py-1.5 text-xs font-normal normal-case tracking-normal">
                                </th>
                                <th class="p-2" style="border-bottom:1px solid #E8E2D2">
                                    <input type="search" x-model="f.phone" @input="queue()" placeholder="Téléphone…"
                                           aria-label="Filtrer par téléphone" class="w-full py-1.5 text-xs font-normal normal-case tracking-normal">
                                </th>
                                <th class="p-2" style="border-bottom:1px solid #E8E2D2">
                                    <input type="search" x-model="f.city" @input="queue()" placeholder="Ville…"
                                           aria-label="Filtrer par ville" class="w-full py-1.5 text-xs font-normal normal-case tracking-normal">
                                </th>
                                <th class="p-2" style="border-bottom:1px solid #E8E2D2">
                                    <select x-model="f.status" @change="load(1)" aria-label="Filtrer par statut"
                                            class="w-full py-1.5 text-xs font-normal normal-case tracking-normal">
                                        <option value="">Tous</option>
                                        @foreach (['prospect', 'actif', 'inactif'] as $s)
                                            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
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

    @include('clients._confirm')

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('clientsTable', (init) => ({
                f: init.filters,
                rows: init.rows,
                links: init.links,
                total: init.total,
                loading: false,
                timer: null,
                ctrl: null,

                get active() {
                    return Object.values(this.f).some(v => v !== '' && v !== null);
                },

                queue() {
                    clearTimeout(this.timer);
                    this.timer = setTimeout(() => this.load(1), 300);
                },

                reset() {
                    Object.keys(this.f).forEach(k => this.f[k] = '');
                    this.load(1);
                },

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
                        this.rows = d.rows;
                        this.links = d.links;
                        this.total = d.total;
                        window.dispatchEvent(new CustomEvent('clients-total', { detail: d.total }));
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
                    const page = parseInt(new URL(a.href, location.origin).searchParams.get('page') || '1', 10);
                    this.load(page);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },
            }));
        });
    </script>
</x-app-layout>