<div x-show="search" x-cloak x-data="globalSearch()"
     x-effect="if (search) $nextTick(() => $refs.q.focus())"
     @keydown.escape.window="search = false"
     class="fixed inset-0 z-50 flex items-start justify-center px-4 pt-24">
    <div class="absolute inset-0 bg-black/60" @click="search = false"></div>

    <div class="relative w-full max-w-xl overflow-hidden rounded-xl bg-white shadow-2xl">
        <div class="flex items-center gap-3 border-b border-gray-200 px-4">
            <x-icon name="search" class="h-5 w-5 text-gray-400" />
            <input x-ref="q" x-model="q" @input.debounce.250ms="run()" @keydown.enter.prevent="go()" type="text"
                   placeholder="Client, devis, commande, facture, produit…"
                   class="w-full border-0 py-4 text-sm focus:ring-0">
            <kbd class="rounded border border-gray-300 px-1.5 text-[11px] text-gray-500">Esc</kbd>
        </div>

        <div class="max-h-96 overflow-y-auto">
            <p x-show="q.trim().length < 2" class="p-4 text-sm text-gray-500">Tapez au moins 2 caractères.</p>
            <p x-show="q.trim().length >= 2 && !loading && groups.length === 0" class="p-4 text-sm text-gray-500">Aucun résultat.</p>

            <template x-for="g in groups" :key="g.title">
                <div>
                    <div class="bg-gray-50 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-widest text-gray-500" x-text="g.title"></div>
                    <template x-for="r in g.items" :key="r.url">
                        <a :href="r.url" class="flex items-baseline justify-between gap-3 px-4 py-2.5 text-sm hover:bg-gold-50">
                            <span class="font-medium text-gray-900" x-text="r.label"></span>
                            <span class="truncate text-xs text-gray-500" x-text="r.sub"></span>
                        </a>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
    function globalSearch() {
        return {
            q: '', groups: [], loading: false, ctrl: null,
            async run() {
                if (this.q.trim().length < 2) { this.groups = []; return; }
                this.loading = true;
                if (this.ctrl) { this.ctrl.abort(); }
                this.ctrl = new AbortController();
                try {
                    const r = await fetch(@js(route('search')) + '?q=' + encodeURIComponent(this.q.trim()), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        signal: this.ctrl.signal,
                    });
                    if (r.ok) { this.groups = (await r.json()).groups; }
                } catch (e) { /* requête annulée */ }
                this.loading = false;
            },
            go() {
                const first = this.groups[0] && this.groups[0].items[0];
                if (first) { window.location.href = first.url; }
            },
        };
    }
</script>