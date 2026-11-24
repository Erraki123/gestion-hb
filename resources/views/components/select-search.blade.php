{{--
    Liste de choix avec recherche AJAX (remplace un <select> de 100+ éléments).

    Utilisation :
    <x-select-search name="client_id" :url="route('clients.search')"
                     :value="old('client_id', $client->id ?? null)"
                     :label="old('client_id') ? '' : ($client->name ?? '')"
                     placeholder="Rechercher un client…" required />

    L'URL doit renvoyer du JSON : [{ "id": 1, "label": "Nom", "sub": "détail facultatif" }]
    Événement émis à chaque choix : « picked » (detail = l'élément choisi).
--}}
@props([
    'name',
    'url',
    'value' => null,
    'label' => '',
    'placeholder' => 'Rechercher…',
    'required' => false,
    'emptyText' => 'Aucun résultat',
])

<div {{ $attributes->merge(['class' => 'relative']) }}
     x-data="selectSearch({ url: @js($url), id: @js($value), label: @js($label), required: @js((bool) $required) })"
     x-on:click.outside="open = false"
     x-on:keydown.escape="open = false">

    <input type="hidden" name="{{ $name }}" value="{{ $value }}" :value="selectedId">

    <div class="relative">
        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" />
        <input type="text" x-ref="input" x-model="query"
               role="combobox" aria-autocomplete="list" aria-haspopup="listbox" :aria-expanded="open.toString()"
               autocomplete="off" placeholder="{{ $placeholder }}"
               :required="required && !selectedId"
               class="w-full pl-10 pr-10"
               @focus="openList()"
               @input="onInput()"
               @keydown.down.prevent="move(1)"
               @keydown.up.prevent="move(-1)"
               @keydown.enter.prevent="if (open && results.length) pick(results[active])">
        <button type="button" x-show="selectedId" x-cloak @click="clear()" aria-label="Effacer"
                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-1 text-gray-500 hover:bg-gold-50 hover:text-gray-900">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>
        </button>
    </div>

    <ul x-show="open" x-cloak x-ref="list" role="listbox"
        class="absolute z-50 mt-2 max-h-72 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white py-1 shadow-xl">
        <template x-for="(r, i) in results" :key="r.id">
            <li role="option" :data-i="i" :aria-selected="(i === active).toString()"
                @mouseenter="active = i" @mousedown.prevent="pick(r)"
                class="cursor-pointer px-4 py-2.5"
                :class="i === active ? 'bg-gold-50' : ''">
                <div class="truncate text-sm font-semibold text-gray-900" x-text="r.label"></div>
                <div class="truncate text-xs text-gray-500" x-show="r.sub" x-text="r.sub"></div>
            </li>
        </template>
        <li x-show="loading && !results.length" class="px-4 py-3 text-sm text-gray-500">Recherche…</li>
        <li x-show="!loading && !results.length" class="px-4 py-3 text-sm text-gray-500">{{ $emptyText }}</li>
    </ul>
</div>

@once
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('selectSearch', (cfg) => ({
                url: cfg.url,
                required: cfg.required,
                selectedId: cfg.id ?? '',
                query: cfg.label ?? '',
                results: [],
                open: false,
                active: 0,
                loading: false,
                timer: null,
                ctrl: null,

                openList() {
                    this.open = true;
                    if (!this.results.length) this.search();
                },

                onInput() {
                    if (this.selectedId) this.$dispatch('cleared');
                    this.selectedId = '';
                    this.open = true;
                    clearTimeout(this.timer);
                    this.timer = setTimeout(() => this.search(), 250);
                },

                async search() {
                    if (this.ctrl) this.ctrl.abort();
                    const mine = this.ctrl = new AbortController();
                    this.loading = true;
                    // Si un choix existe déjà, on réaffiche toute la liste au lieu de chercher son nom
                    const q = this.selectedId ? '' : this.query.trim();
                    try {
                        const r = await fetch(this.url + (this.url.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(q), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            signal: mine.signal,
                        });
                        if (r.status === 401 || r.status === 419) { location.reload(); return; }
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        this.results = await r.json();
                        this.active = 0;
                    } catch (e) {
                        if (e.name !== 'AbortError') console.error(e);
                    } finally {
                        if (this.ctrl === mine) this.loading = false;
                    }
                },

                move(d) {
                    if (!this.open) { this.openList(); return; }
                    const n = this.results.length;
                    if (!n) return;
                    this.active = (this.active + d + n) % n;
                    this.$nextTick(() => {
                        const el = this.$refs.list.querySelector('[data-i="' + this.active + '"]');
                        if (el) el.scrollIntoView({ block: 'nearest' });
                    });
                },

                pick(r) {
                    if (!r) return;
                    this.selectedId = r.id;
                    this.query = r.label;
                    this.open = false;
                    this.$dispatch('picked', r);
                },

                clear() {
                    this.$dispatch('cleared');
                    this.selectedId = '';
                    this.query = '';
                    this.search();
                    this.$refs.input.focus();
                },
            }));
        });
    </script>
@endonce
