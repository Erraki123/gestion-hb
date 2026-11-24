@csrf
@php
    $cid         = old('client_id', $selectedClient ?? null);
    $clientLabel = $cid ? (\App\Models\Client::find($cid)?->name ?? '') : '';
    $lbl         = 'mb-1 block text-xs font-medium text-gray-600';
@endphp

<div x-data="quoteForm(@js($initialItems), {{ auth()->user()->isAdmin() ? 'true' : 'false' }}, @js($mode), {{ $defaultRate }})">

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">
            @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start">

        {{-- ================= MOITIÉ GAUCHE ================= --}}
        <div class="space-y-6">

            {{-- Client et dates --}}
            <div class="panel p-6">
                <h3 class="panel-title mb-4">Client et dates</h3>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Client *</label>
                        <x-select-search name="client_id" :url="route('clients.search')"
                                         :value="$cid" :label="$clientLabel"
                                         placeholder="Tapez le nom du client…" emptyText="Aucun client trouvé" required />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="issue_date" value="Date du devis *" />
                            <x-text-input id="issue_date" name="issue_date" type="date" class="mt-1 block w-full"
                                :value="old('issue_date', optional($quote?->issue_date)->format('Y-m-d') ?? now()->format('Y-m-d'))" required />
                        </div>
                        <div>
                            <x-input-label for="valid_until" value="Valable jusqu'au" />
                            <x-text-input id="valid_until" name="valid_until" type="date" class="mt-1 block w-full"
                                :value="old('valid_until', optional($quote?->valid_until)->format('Y-m-d') ?? now()->addDays(config('commerce.quote_validity_days'))->format('Y-m-d'))" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Conditions et notes --}}
            <div class="panel p-6">
                <h3 class="panel-title mb-4">Conditions et notes</h3>

                <div class="space-y-4">
                    <div>
                        <x-input-label for="terms" value="Conditions (visibles sur le PDF)" />
                        <textarea id="terms" name="terms" rows="4" class="mt-1 block w-full"
                                  placeholder="Ex : paiement à 30 jours, livraison sous 7 jours…">{{ old('terms', $quote->terms ?? '') }}</textarea>
                    </div>
                    <div>
                        <x-input-label for="notes" value="Notes internes (non visibles sur le PDF)" />
                        <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full"
                                  placeholder="Note pour votre équipe uniquement">{{ old('notes', $quote->notes ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= MOITIÉ DROITE ================= --}}
        <div class="space-y-6">

            {{-- Produits --}}
            <div class="panel p-6">
                <h3 class="panel-title mb-4">Produits</h3>

                {{-- Aucun champ produit au départ : on choisit comment ajouter la première ligne --}}
                <div id="rows-empty" x-show="rows.length === 0" x-cloak
                     class="rounded-lg border-2 border-dashed px-4 py-8 text-center"
                     :class="noRows ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-gray-50'">
                    <p class="text-sm font-semibold text-gray-900">Aucun produit pour le moment</p>
                    <p class="mt-1 text-sm" :class="noRows ? 'font-semibold text-red-700' : 'text-gray-500'"
                       x-text="noRows ? 'Ajoutez au moins un produit ou une ligne libre pour enregistrer le devis.' : 'Choisissez comment ajouter la première ligne :'"></p>
                    <div class="mt-4 flex flex-wrap justify-center gap-3">
                        <button type="button" @click="add()"
                                class="rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">+ Ajouter depuis le stock</button>
                        <button type="button" @click="addFree()"
                                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 hover:border-gold-500 hover:bg-gold-50">+ Ligne libre</button>
                    </div>
                </div>

                <div class="space-y-3">
                    <template x-for="(row, i) in rows" :key="row.key">
                        <div class="rounded-xl border border-gray-200 bg-white p-3">

                            {{-- Produit + bouton supprimer --}}
                            <div class="flex items-start gap-2">
                                <div class="min-w-0 flex-1">
                                    <template x-if="!row.is_free"><div>
<label class="{{ $lbl }}">Produit *</label>
                                    <div class="relative"
                                         x-data="selectSearch({ url: @js(route('quotes.products.search')), id: row.product_id, label: row.product_label, required: true })"
                                         @picked="onPick(row, $event.detail)"
                                         @cleared="row.product_id = ''; row.stock = null"
                                         x-on:click.outside="open = false" x-on:keydown.escape="open = false">
                                        <input type="hidden" :name="`items[${i}][product_id]`" :value="row.product_id">
                                        <div class="relative">
                                            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" />
                                            <input type="text" x-ref="input" :id="'prod-' + row.key" x-model="query"
                                                   role="combobox" autocomplete="off" placeholder="Tapez le nom ou le code…"
                                                   :required="required && !selectedId" class="w-full pl-10 pr-9"
                                                   @focus="openList()" @input="onInput()"
                                                   @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)"
                                                   @keydown.enter.prevent="if (open && results.length) pick(results[active])">
                                            <button type="button" x-show="selectedId" x-cloak @click="clear()" aria-label="Effacer"
                                                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-1 text-gray-500 hover:bg-gold-50 hover:text-gray-900">✕</button>
                                        </div>
                                        <ul x-show="open" x-cloak x-ref="list" role="listbox"
                                            class="absolute z-50 mt-2 max-h-72 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white py-1 shadow-xl">
                                            <template x-for="(r, k) in results" :key="r.id">
                                                <li role="option" :data-i="k" @mouseenter="active = k" @mousedown.prevent="pick(r)"
                                                    class="cursor-pointer px-4 py-2.5" :class="k === active ? 'bg-gold-50' : ''">
                                                    <div class="truncate text-sm font-semibold text-gray-900" x-text="r.label"></div>
                                                    <div class="truncate text-xs text-gray-500" x-text="r.sub"></div>
                                                </li>
                                            </template>
                                            <li x-show="loading && !results.length" class="px-4 py-3 text-sm text-gray-500">Recherche…</li>
                                            <li x-show="!loading && !results.length" class="px-4 py-3 text-sm text-gray-500">Aucun produit trouvé</li>
                                        </ul>
                                    </div>
                                    <p x-show="row.stock !== null" x-cloak class="mt-1 text-xs"
                                       :class="row.quantity > row.stock ? 'font-semibold text-red-600' : 'text-gray-500'"
                                       x-text="'Stock disponible : ' + num(row.stock, 2) + (row.quantity > row.stock ? ' — quantité trop grande' : '')"></p>
</div></template>
                                    <template x-if="row.is_free">
                                        <div>
                                            <input type="hidden" :name="`items[${i}][product_id]`" value="">
                                            <div class="grid grid-cols-3 gap-2">
                                                <div class="col-span-2">
                                                    <label class="{{ $lbl }}">Désignation *</label>
                                                    <input type="text" maxlength="255" :id="'name-' + row.key" :name="`items[${i}][name]`"
                                                           x-model="row.name" placeholder="Ex : Installation réseau" class="w-full" required>
                                                </div>
                                                <div>
                                                    <label class="{{ $lbl }}">Unité</label>
                                                    <select :name="`items[${i}][unit]`" x-model="row.unit" class="w-full">
                                                        @foreach (\App\Services\DocumentLines::FREE_UNITS as $u)
                                                            <option value="{{ $u }}">{{ $u }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-500">● ligne libre : sans stock</p>
                                        </div>
                                    </template>

                                </div>

                                {{-- On peut supprimer toutes les lignes, même la dernière --}}
                                <button type="button" @click="remove(i)"
                                        title="Supprimer cette ligne" aria-label="Supprimer cette ligne"
                                        class="act act-danger mt-5">✕</button>
                            </div>

                            {{-- Quantité, prix, TVA, remise --}}
                            <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div>
                                    <label class="{{ $lbl }}">Quantité</label>
                                    <input type="number" step="0.01" min="0.01" :id="'qty-' + row.key" :name="`items[${i}][quantity]`"
                                           x-model.number="row.quantity" class="w-full" required>
                                </div>
                                <div>
                                    <label class="{{ $lbl }}">Prix {{ $mode === 'ttc' ? 'TTC' : 'HT' }}</label>
                                    <input type="number" step="0.0001" min="0" x-model="row.price_input" @input="recalc(row)"
                                           :readonly="!canEdit(row)" :required="row.is_free" :class="canEdit(row) ? '' : 'bg-gray-100 opacity-80'" class="w-full">
                                    <input type="hidden" :name="`items[${i}][unit_price]`" :value="row.unit_price">
                                </div>
                                <div>
                                    <label class="{{ $lbl }}">TVA</label>
                                    <select :name="canEdit(row) ? `items[${i}][tax_rate]` : null" :disabled="!canEdit(row)"
                                            x-model.number="row.tax_rate" @change="recalc(row)"
                                            :class="canEdit(row) ? '' : 'bg-gray-100 opacity-80'" class="w-full">
                                        @foreach ($rates as $r)
                                            <option value="{{ $r + 0 }}">{{ $r + 0 }} %</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" :name="!canEdit(row) ? `items[${i}][tax_rate]` : null" :value="row.tax_rate">
                                </div>
                                <div>
                                    <label class="{{ $lbl }}">Remise %</label>
                                    <input type="number" step="0.01" min="0" max="100"
                                           :name="`items[${i}][discount_percent]`"
                                           x-model.number="row.discount_percent" class="w-full">
                                </div>
                            </div>

                            {{-- Total de la ligne --}}
                            <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-2">
                                <span class="text-xs font-medium text-gray-500">Total HT</span>
                                <span class="text-base font-bold text-gray-900" x-text="fmt(lineHt(row))"></span>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Boutons d'ajout : visibles quand il y a déjà au moins une ligne --}}
                <div class="mt-3 flex flex-wrap gap-2" x-show="rows.length > 0" x-cloak>
                    <button type="button" @click="add()" class="act">+ Ajouter depuis le stock</button>
                    <button type="button" @click="addFree()" class="act">+ Ligne libre</button>
                </div>
            </div>

            {{-- Récapitulatif --}}
            <div class="panel p-6">
                <h3 class="panel-title mb-4">Récapitulatif</h3>

                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Total HT</span>
                        <span class="text-gray-900" x-text="fmt(subtotal())"></span>
                    </div>
                    <template x-for="b in breakdown()" :key="b.rate">
                        <div class="flex justify-between text-gray-500">
                            <span>TVA <span x-text="b.rate"></span> %</span>
                            <span x-text="fmt(b.tax)"></span>
                        </div>
                    </template>
                    <div class="flex justify-between border-t border-gray-200 pt-3 text-lg font-bold text-gray-900">
                        <span>Total TTC</span>
                        <span x-text="fmt(total())"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Boutons --}}
    <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route('quotes.index') }}" class="text-sm text-gray-500 hover:text-gray-900">Annuler</a>
        <button @click="blockIfEmpty($event)"
                class="rounded-lg bg-gold-500 px-6 py-3 text-sm font-semibold text-gray-950 hover:bg-gold-400">Enregistrer le devis</button>
    </div>
</div>

<script>
    function quoteForm(initial, isAdmin, mode, defaultRate) {
        let seq = 0;
        const r2 = (n) => Math.round((n + Number.EPSILON) * 100) / 100;
        const r4 = (n) => Math.round((n + Number.EPSILON) * 10000) / 10000;
        const shown = (ht, rate) => mode === 'ttc' ? r2(ht * (1 + rate / 100)) : r4(ht);

        const mk = (r = {}) => {
            const rate = parseFloat(r.tax_rate ?? defaultRate);
            const ht   = parseFloat(r.unit_price ?? 0) || 0;

            return {
                key: ++seq, product_id: r.product_id ?? '', product_label: r.product_label ?? '',
                is_free: !!r.is_free, name: r.name ?? '', unit: r.unit ?? 'unité',
                quantity: r.quantity ?? 1, unit_price: ht, tax_rate: rate,
                discount_percent: r.discount_percent ?? 0, price_input: shown(ht, rate),
                stock: null,
            };
        };

        return {
            isAdmin, mode,
            noRows: false,
            // Départ : aucune ligne (sauf en modification d'un devis existant)
            rows: initial.map(mk),

            add() {
                const row = mk();
                this.rows.push(row);
                this.noRows = false;
                this.$nextTick(() => { const el = document.getElementById('prod-' + row.key); if (el) el.focus(); });
            },
            addFree() {
                const row = mk({ is_free: true });
                this.rows.push(row);
                this.noRows = false;
                this.$nextTick(() => { const el = document.getElementById('name-' + row.key); if (el) el.focus(); });
            },
            canEdit(row) { return this.isAdmin || row.is_free; },
            remove(i) { this.rows.splice(i, 1); },

            // Empêche l'enregistrement sans aucune ligne
            blockIfEmpty(e) {
                if (this.rows.length === 0) {
                    e.preventDefault();
                    this.noRows = true;
                    this.$nextTick(() => {
                        const el = document.getElementById('rows-empty');
                        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    });
                }
            },

            // Produit choisi : prix, TVA et stock viennent du serveur
            onPick(row, p) {
                const d = p.data || {};
                row.product_id = p.id;
                row.product_label = p.label;
                row.tax_rate = parseFloat(d.tax_rate ?? defaultRate);
                row.unit_price = r4(parseFloat(d.sale_price ?? 0));
                row.price_input = shown(row.unit_price, row.tax_rate);
                row.stock = d.stock ?? null;
                this.$nextTick(() => { const el = document.getElementById('qty-' + row.key); if (el) { el.focus(); el.select(); } });
            },

            recalc(row) {
                const v = parseFloat(row.price_input);
                row.unit_price = isNaN(v) ? 0 : (mode === 'ttc' ? r4(v / (1 + row.tax_rate / 100)) : r4(v));
            },
            lineHt(r) { return r2((r.quantity || 0) * (r.unit_price || 0) * (1 - (r.discount_percent || 0) / 100)); },
            subtotal() { return r2(this.rows.reduce((s, r) => s + this.lineHt(r), 0)); },
            breakdown() {
                const g = {};
                this.rows.forEach(r => { const k = Number(r.tax_rate).toFixed(2); g[k] = (g[k] || 0) + this.lineHt(r); });
                return Object.keys(g).sort((a, b) => b - a).map(k => {
                    const base = r2(g[k]);
                    return { rate: parseFloat(k), base, tax: r2(base * parseFloat(k) / 100) };
                });
            },
            tax() { return r2(this.breakdown().reduce((s, b) => s + b.tax, 0)); },
            total() { return r2(this.subtotal() + this.tax()); },
            num(n, d = 2) { return Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: d }); },
            fmt(n) { return this.num(n, 2) + ' DH'; },
        };
    }
</script>
