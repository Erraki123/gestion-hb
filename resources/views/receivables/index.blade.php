@php
    $isAdmin = auth()->user()->isAdmin();
    $fmt     = fn ($n) => number_format((float) $n, 2, ',', ' ');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="grid items-center gap-3 text-center lg:grid-cols-3">
            <div class="flex justify-center lg:justify-start">
                <a href="{{ route('invoices.index') }}"
                   class="inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">← Factures</a>
            </div>

            <div>
                <h2 class="justify-center !text-lg !leading-tight">Créances</h2>
                <p class="text-xs text-gray-500">Reste à encaisser, par ancienneté</p>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-3 text-xs lg:justify-end">
                <a href="{{ route('payments.index') }}" class="link">Paiements</a>
                <a href="{{ route('returns.index') }}" class="link">Retours</a>
            </div>
        </div>
    </x-slot>

    <style>
        header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }
        .list-compact table { font-size: .8rem; }
        .list-compact thead th { padding: .5rem .75rem; font-size: .68rem; }
        .list-compact tbody td { padding: .4rem .75rem; }
        .list-compact tbody td .act { padding: .2rem .55rem; font-size: .7rem; }
    </style>

    <div class="list-compact max-w-6xl mx-auto px-4 py-3 sm:px-6 lg:px-8 space-y-3"
         x-data="receivablesPage(@js(['url' => route('receivables.index'), 'commercial' => (string) request('commercial_id', '')]))">

        {{-- Recherche : le client se filtre sur place, le commercial recharge sans quitter la page --}}
        <div class="panel p-3">
            <div class="flex flex-wrap items-center gap-2">
                <input type="search" x-model="q" @input="applySearch()" aria-label="Chercher un client"
                       placeholder="🔎 Chercher un client…" class="w-full py-1.5 text-xs sm:w-72">
                @if ($isAdmin)
                    <select x-model="commercial" @change="load()" aria-label="Commercial" class="w-full py-1.5 text-xs sm:w-auto">
                        <option value="">Commercial : tous</option>
                        @foreach ($commerciaux as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                @endif
                <button type="button" x-show="active" x-cloak @click="reset()"
                        class="text-xs font-semibold text-gray-900 underline decoration-gold-500 decoration-2 underline-offset-4">
                    Réinitialiser
                </button>
            </div>
        </div>

        <div id="recv-content" x-ref="content" class="space-y-3 transition-opacity" :class="loading ? 'opacity-50' : ''">

            {{-- Les chiffres : une seule ligne (remplace les 5 cartes, les colonnes du tableau détaillent par client) --}}
            <p class="text-sm text-gray-600">
                À encaisser <strong class="text-gray-900">{{ $fmt($totals['total']) }}</strong>
                · Non échu <strong class="text-gray-900">{{ $fmt($totals['not_due']) }}</strong>
                @if (($totals['cheques'] ?? 0) > 0)
                    · Chèques en attente <strong class="text-yellow-700">{{ $fmt($totals['cheques']) }}</strong>
                @endif
                · 1–30 j <strong class="text-yellow-700">{{ $fmt($totals['d30']) }}</strong>
                · 31–60 j <strong class="text-orange-600">{{ $fmt($totals['d60']) }}</strong>
                · +60 j <strong class="text-red-700">{{ $fmt($totals['d60p']) }}</strong>
                DH
            </p>

            <div class="panel overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left">
                        <tr>
                            <th class="p-4">Client</th>
                            <th class="p-4">Factures</th>
                            <th class="p-4 text-right">Non échu</th>
                            <th class="p-4 text-right">1–30 j</th>
                            <th class="p-4 text-right">31–60 j</th>
                            <th class="p-4 text-right">+60 j</th>
                            <th class="p-4 text-right">Chèques en attente</th>
                            <th class="p-4 text-right">Total à encaisser</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $r)
                        <tr data-name="{{ \Illuminate\Support\Str::lower($r['client']->name . ' ' . ($r['client']->company ?? '')) }}">
                            <td class="p-4 font-semibold">
                                <a class="text-gray-900 hover:text-gold-400" href="{{ route('clients.show', $r['client']) }}">{{ $r['client']->name }}</a>
                            </td>
                            <td class="p-4">
                                @if ($r['count'] > 0)
                                    <a class="act" href="{{ route('invoices.index', ['client' => $r['client']->name, 'tab' => 'a_encaisser']) }}">{{ $r['count'] }}</a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="p-4 text-right text-gray-700">{{ $fmt($r['buckets']['not_due']) }}</td>
                            <td class="p-4 text-right text-gray-700">{{ $fmt($r['buckets']['d30']) }}</td>
                            <td class="p-4 text-right text-gray-700">{{ $fmt($r['buckets']['d60']) }}</td>
                            <td class="p-4 text-right text-red-600">{{ $fmt($r['buckets']['d60p']) }}</td>
                            <td class="p-4 text-right">
                                @if (($r['cheques'] ?? 0) > 0)
                                    <a class="font-medium text-yellow-700 underline decoration-yellow-400 underline-offset-4"
                                       href="{{ route('invoices.index', ['client' => $r['client']->name, 'cheque_status' => 'en_attente']) }}">{{ $fmt($r['cheques']) }}</a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="p-4 text-right font-bold text-gray-900">{{ $fmt($r['total']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-10 text-center text-gray-500">Aucune créance 🎉</td>
                        </tr>
                    @endforelse
                        <tr data-none hidden>
                            <td colspan="8" class="p-10 text-center text-gray-500">Aucun client ne correspond à votre recherche.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('receivablesPage', (init) => ({
                q: '', commercial: init.commercial, loading: false, ctrl: null,

                get active() { return this.q !== '' || this.commercial !== ''; },

                applySearch() {
                    const term = this.q.trim().toLowerCase();
                    const root = this.$refs.content;
                    let shown = 0;
                    root.querySelectorAll('tr[data-name]').forEach(tr => {
                        const ok = term === '' || tr.dataset.name.includes(term);
                        tr.hidden = !ok;
                        if (ok) shown++;
                    });
                    const none = root.querySelector('tr[data-none]');
                    if (none) none.hidden = !(term !== '' && shown === 0 && root.querySelector('tr[data-name]'));
                },

                reset() { this.q = ''; this.commercial = ''; this.load(); },

                async load() {
                    if (this.ctrl) this.ctrl.abort();
                    const mine = this.ctrl = new AbortController();
                    this.loading = true;
                    const qs = this.commercial ? '?commercial_id=' + encodeURIComponent(this.commercial) : '';
                    try {
                        const r = await fetch(init.url + qs, {
                            headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                            signal: mine.signal,
                        });
                        if (r.status === 401 || r.status === 419) { location.reload(); return; }
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        const doc  = new DOMParser().parseFromString(await r.text(), 'text/html');
                        const next = doc.getElementById('recv-content');
                        if (next) {
                            this.$refs.content.innerHTML = next.innerHTML;
                            this.applySearch();
                        }
                        history.replaceState(null, '', init.url + qs);
                    } catch (e) {
                        if (e.name !== 'AbortError') console.error(e);
                    } finally {
                        if (this.ctrl === mine) this.loading = false;
                    }
                },
            }));
        });
    </script>
</x-app-layout>
