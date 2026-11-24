<x-app-layout dark-header>
    <x-slot name="header">
        <div class="relative flex items-center justify-center text-center">
            <div>
                <h2 class="justify-center !text-lg !leading-tight">Nouvelle facture</h2>
                <p class="text-xs text-gray-500">Client, produits, échéance et paiement : tout est sur cette page</p>
            </div>
            <a href="{{ route('invoices.index') }}"
               class="absolute right-0 top-1/2 -translate-y-1/2 inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">
                ← Retour à la liste
            </a>
        </div>
    </x-slot>

    <style>
        header, header > div { padding-top: .5rem !important; padding-bottom: .5rem !important; }
    </style>

    @php
        $cid         = old('client_id', $selectedClient ?? null);
        $clientLabel = $cid ? (\App\Models\Client::find($cid)?->name ?? '') : '';

        $old   = collect(old('items', []));
        $names = $old->isNotEmpty()
            ? \App\Models\Product::whereIn('id', $old->pluck('product_id')->filter())->pluck('name', 'id')
            : collect();

        $initialItems = $old->map(fn ($r) => [
            'product_id'       => $r['product_id'] ?? '',
            'product_label'    => $names->get($r['product_id'] ?? 0, ''),
            'is_free'          => empty($r['product_id']),
            'name'             => $r['name'] ?? '',
            'unit'             => $r['unit'] ?? 'unité',
            'quantity'         => (float) ($r['quantity'] ?? 1),
            'unit_price'       => (float) ($r['unit_price'] ?? 0),
            'tax_rate'         => (float) ($r['tax_rate'] ?? $defaultRate),
            'discount_percent' => (float) ($r['discount_percent'] ?? 0),
        ])->values();

        $lbl = 'mb-1 block text-xs font-medium text-gray-600 lg:hidden';
        $lb2 = 'mb-1 block text-xs font-medium text-gray-600';
    @endphp

    <div class="py-3 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('invoices.store') }}" enctype="multipart/form-data" class="panel p-6 sm:p-8">
            @csrf

            <div @extras-changed.window="setExtras($event.detail)" x-data="invoiceForm(@js($initialItems), {{ auth()->user()->isAdmin() ? 'true' : 'false' }}, @js($mode), {{ $defaultRate }}, {{ (int) $defaultDays }}, @js(old('terms_days')), @js(old('due_date')), {{ old('action') === 'issue_pay' ? 'true' : 'false' }}, @js(old('method', 'especes')), @js(old('amount')))">

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">
                        @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
                    </div>
                @endif

                <input type="hidden" name="action" :value="action">

                {{-- 1. CLIENT --}}
                <section class="mb-8">
                    <h3 class="mb-3 text-base font-bold text-gray-900">1. Client</h3>
                    <x-select-search class="max-w-xl" name="client_id" :url="route('clients.search')"
                                     :value="$cid" :label="$clientLabel"
                                     placeholder="Tapez le nom du client…" emptyText="Aucun client trouvé" required />
                    <a href="{{ route('clients.create') }}" target="_blank" class="link mt-2 inline-block text-sm">+ Nouveau client (nouvel onglet)</a>
                </section>

                {{-- 2. PRODUITS : produit, quantité, prix, TVA, remise, total --}}
                <section class="mb-8">
                    <h3 class="mb-3 text-base font-bold text-gray-900">2. Produits</h3>

                    <div class="mb-2 hidden grid-cols-12 gap-3 px-3 text-xs font-semibold text-gray-600 lg:grid">
                        <div class="col-span-4">Produit</div>
                        <div class="col-span-1">Quantité</div>
                        <div class="col-span-2">Prix {{ $mode === 'ttc' ? 'TTC' : 'HT' }}</div>
                        <div class="col-span-1">TVA</div>
                        <div class="col-span-1">Remise %</div>
                        <div class="col-span-2 text-right">Total HT</div>
                        <div class="col-span-1"></div>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(row, i) in rows" :key="row.key">
                            <div class="rounded-xl border border-gray-200 bg-white p-3">
                                <div class="grid grid-cols-6 items-start gap-3 lg:grid-cols-12">

                                    <div class="col-span-6 lg:col-span-4">
                                        <template x-if="!row.is_free"><div>
<label class="{{ $lbl }}">Produit</label>
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
                                                    <label class="{{ $lb2 }}">Désignation *</label>
                                                    <input type="text" maxlength="255" :id="'name-' + row.key" :name="`items[${i}][name]`"
                                                           x-model="row.name" placeholder="Ex : Installation réseau" class="w-full" required>
                                                </div>
                                                <div>
                                                    <label class="{{ $lb2 }}">Unité</label>
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

                                    <div class="col-span-3 lg:col-span-1">
                                        <label class="{{ $lbl }}">Quantité</label>
                                        <input type="number" step="0.01" min="0.01" :id="'qty-' + row.key" :name="`items[${i}][quantity]`"
                                               x-model.number="row.quantity" class="w-full" required>
                                    </div>

                                    <div class="col-span-3 lg:col-span-2">
                                        <label class="{{ $lbl }}">Prix {{ $mode === 'ttc' ? 'TTC' : 'HT' }}</label>
                                        <input type="number" step="0.0001" min="0" x-model="row.price_input" @input="recalc(row)"
                                               :readonly="!canEdit(row)" :required="row.is_free" :class="canEdit(row) ? '' : 'bg-gray-100 opacity-80'" class="w-full">
                                        <input type="hidden" :name="`items[${i}][unit_price]`" :value="row.unit_price">
                                    </div>

                                    {{-- TVA : toujours visible (vient du produit) --}}
                                    <div class="col-span-3 lg:col-span-1">
                                        <label class="{{ $lbl }}">TVA</label>
                                        <template x-if="row.is_free">
                                            <select :name="`items[${i}][tax_rate]`" x-model.number="row.tax_rate" @change="recalc(row)" class="w-full">
                                                @foreach ($rates as $r)
                                                    <option value="{{ $r + 0 }}">{{ $r + 0 }} %</option>
                                                @endforeach
                                            </select>
                                        </template>
                                        <template x-if="!row.is_free">
                                            <div>
                                                <input type="text" readonly tabindex="-1" :value="row.tax_rate + ' %'" class="w-full bg-gray-100 opacity-80">
                                                <input type="hidden" :name="`items[${i}][tax_rate]`" :value="row.tax_rate">
                                            </div>
                                        </template>
                                    </div>

                                    {{-- Remise : toujours visible --}}
                                    <div class="col-span-3 lg:col-span-1">
                                        <label class="{{ $lbl }}">Remise %</label>
                                        <input type="number" step="0.01" min="0" max="100" :name="`items[${i}][discount_percent]`"
                                               x-model.number="row.discount_percent" class="w-full">
                                    </div>

                                    <div class="col-span-5 lg:col-span-2">
                                        <label class="{{ $lbl }} text-right">Total HT</label>
                                        <div class="pt-2 text-right text-base font-bold text-gray-900" x-text="fmt(lineHt(row))"></div>
                                    </div>

                                    <div class="col-span-1 flex justify-end pt-1 lg:pt-0">
                                        <button type="button" @click="remove(i)" x-show="rows.length > 1"
                                                title="Supprimer cette ligne" aria-label="Supprimer cette ligne"
                                                class="act act-danger">✕</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" @click="add()" class="act">+ Ajouter un produit</button>
                        <button type="button" @click="addFree()" class="act">+ Ligne libre</button>
                    </div>
                </section>

                {{-- TRANSPORT ET INSTALLATION (facultatifs, sans TVA, ajoutés au total) --}}
                <section class="mb-8 border-t border-gray-200 pt-6">
                    <h3 class="mb-3 text-base font-bold text-gray-900">Transport et installation <span class="text-xs font-normal text-gray-500">(facultatif)</span></h3>
                    @include('invoices._extras', ['transport' => 0, 'installation' => 0])
                </section>

                {{-- 3. ÉCHÉANCE --}}
                <section class="mb-8 border-t border-gray-200 pt-6">
                    <h3 class="mb-3 text-base font-bold text-gray-900">3. Échéance</h3>

                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <p class="{{ $lb2 }}">Paiement attendu</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="o in termOptions" :key="o.v">
                                    <button type="button" @click="setDays(o.v)"
                                            class="rounded-lg border px-4 py-2 text-sm font-medium"
                                            :class="isDays(o.v) ? 'border-gray-950 bg-gray-950 text-gold-300' : 'border-gray-300 bg-white text-gray-700 hover:bg-gold-50'"
                                            x-text="o.l"></button>
                                </template>
                            </div>
                        </div>
                        <div>
                            <label for="due_date" class="{{ $lb2 }}">Date d'échéance</label>
                            <input id="due_date" type="date" name="due_date" x-model="dueDate" required
                                   min="{{ now()->format('Y-m-d') }}" class="block">
                        </div>
                    </div>
                </section>

                {{-- 4. PAIEMENT : deux choix toujours visibles --}}
                <section class="mb-8 border-t border-gray-200 pt-6">
                    <h3 class="mb-3 text-base font-bold text-gray-900">4. Paiement</h3>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <button type="button" @click="payNow = false"
                                class="rounded-xl border-2 p-4 text-left"
                                :class="!payNow ? 'border-gold-500 bg-gold-50' : 'border-gray-200 bg-white hover:bg-gold-50'">
                            <span class="block text-base font-bold text-gray-900">Le client paiera plus tard</span>
                            <span class="mt-1 block text-sm text-gray-600">La facture est enregistrée. Vous encaisserez avant l'échéance.</span>
                        </button>
                        <button type="button" @click="payNow = true"
                                class="rounded-xl border-2 p-4 text-left"
                                :class="payNow ? 'border-gold-500 bg-gold-50' : 'border-gray-200 bg-white hover:bg-gold-50'">
                            <span class="block text-base font-bold text-gray-900">Le client paie maintenant</span>
                            <span class="mt-1 block text-sm text-gray-600">Vous enregistrez la facture et l'argent reçu en une seule fois.</span>
                        </button>
                    </div>

                    <div x-show="payNow" x-cloak class="mt-4 space-y-4 rounded-xl border border-gray-200 p-4"
                         x-effect="if (payNow && !touched) amount = total()">
                        <div>
                            <p class="{{ $lb2 }}">Mode de paiement</p>
                            <div class="grid grid-cols-3 gap-3">
                                <template x-for="m in methods" :key="m.v">
                                    <button type="button" @click="method = m.v"
                                            class="rounded-lg border px-3 py-3 text-sm font-semibold"
                                            :class="method === m.v ? 'border-gray-950 bg-gray-950 text-gold-300' : 'border-gray-300 bg-white text-gray-700 hover:bg-gold-50'"
                                            x-text="m.l"></button>
                                </template>
                            </div>
                            <input type="hidden" name="method" :value="method" :disabled="!payNow">
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="{{ $lb2 }}">Montant encaissé (DH) — modifiable pour un paiement partiel</label>
                                <input type="number" step="0.01" min="0.01" name="amount" x-model="amount"
                                       @input="touched = true" :disabled="!payNow" :required="payNow" class="w-full text-lg font-bold">
                            </div>
                            <div x-show="method !== 'especes'" x-cloak>
                                <label class="{{ $lb2 }}">Référence (facultatif)</label>
                                <input type="text" name="reference" maxlength="100" :disabled="!payNow || method === 'especes'" class="w-full">
                            </div>
                        </div>

                        <div x-show="method === 'cheque'" x-cloak class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="{{ $lb2 }}">N° du chèque</label>
                                <input type="text" name="cheque_number" maxlength="50" :disabled="!payNow || method !== 'cheque'" class="w-full">
                            </div>
                            <div>
                                <label class="{{ $lb2 }}">Banque</label>
                                <input type="text" name="cheque_bank" maxlength="100" :disabled="!payNow || method !== 'cheque'" class="w-full">
                            </div>
                            <div>
                                <label class="{{ $lb2 }}">Date d'encaissement du chèque</label>
                                <input type="date" name="cheque_due_date" :disabled="!payNow || method !== 'cheque'" class="w-full">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @include('invoices._pay_files', ['offCheque' => "!payNow || method !== 'cheque'"])
                        </div>
                    </div>
                </section>

                {{-- 5. NOTE : toujours visible --}}
                <section class="mb-2 border-t border-gray-200 pt-6">
                    <h3 class="mb-3 text-base font-bold text-gray-900">5. Note (facultatif)</h3>
                    <textarea name="notes" rows="2" class="block w-full"
                              placeholder="Note visible sur la facture">{{ old('notes') }}</textarea>
                </section>

                {{-- Barre du bas --}}
                <div class="sticky bottom-0 -mx-6 -mb-6 mt-8 flex flex-wrap items-center justify-between gap-4 rounded-b-2xl border-t border-gray-200 bg-white/95 px-6 py-4 backdrop-blur sm:-mx-8 sm:-mb-8 sm:px-8">
                    <div>
                        <div class="text-xs font-medium text-gray-500">Total TTC</div>
                        <div class="text-2xl font-bold tracking-tight text-gray-900" x-text="fmt(total())"></div>
                        <div class="text-xs text-gray-500">Total HT : <span x-text="fmt(subtotal())"></span></div>
                        <div class="text-xs text-gray-500" x-show="extraT + extraI > 0" x-cloak>dont transport / installation (sans TVA) : <span x-text="fmt(extraT + extraI)"></span></div>
                        <template x-for="b in breakdown()" :key="b.rate">
                            <div class="text-xs text-gray-500">TVA <span x-text="b.rate"></span> % : <span x-text="fmt(b.tax)"></span></div>
                        </template>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('invoices.index') }}" class="text-sm text-gray-500 hover:text-gray-900">Annuler</a>
                        <button type="button" @click="send(payNow ? 'issue_pay' : 'issue')" :disabled="busy"
                                class="rounded-lg bg-gold-500 px-8 py-3 text-sm font-semibold text-gray-950 disabled:opacity-60"
                                x-text="busy ? 'Enregistrement…' : (payNow ? 'Enregistrer et encaisser' : 'Enregistrer la facture')"></button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function invoiceForm(initial, isAdmin, mode, defaultRate, defaultDays, oldTerms, oldDue, payNow, method, amount) {
            let seq = 0;
            const r2 = (n) => Math.round((n + Number.EPSILON) * 100) / 100;
            const r4 = (n) => Math.round((n + Number.EPSILON) * 10000) / 10000;
            const shown = (ht, rate) => mode === 'ttc' ? r2(ht * (1 + rate / 100)) : r4(ht);

            // Date locale AAAA-MM-JJ, aujourd'hui + n jours
            const inDays = (n) => {
                const d = new Date();
                d.setDate(d.getDate() + n);
                return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            };

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

            const startDue = oldDue
                ? oldDue
                : inDays((oldTerms !== null && oldTerms !== '' && !isNaN(parseInt(oldTerms))) ? parseInt(oldTerms) : defaultDays);

            return {
                isAdmin, mode, dueDate: startDue, payNow, method, amount: amount || '',
                extraT: 0, extraI: 0,
                touched: !!amount, busy: false, action: '',
                rows: initial.length ? initial.map(mk) : [mk()],
                termOptions: [
                    { v: 0,  l: 'À réception' },
                    { v: 15, l: '15 jours' },
                    { v: 30, l: '30 jours' },
                    { v: 60, l: '60 jours' },
                ],
                methods: [
                    { v: 'especes',  l: 'Espèces' },
                    { v: 'cheque',   l: 'Chèque' },
                    { v: 'virement', l: 'Virement' },
                ],

                setDays(n) { this.dueDate = inDays(n); },
                isDays(n)  { return this.dueDate === inDays(n); },

                send(a) {
                    if (this.busy) return;
                    this.action = a;
                    this.$nextTick(() => {
                        const form = this.$root.closest('form');
                        if (form.checkValidity()) { this.busy = true; }
                        form.requestSubmit();
                    });
                },

                add() {
                    const row = mk();
                    this.rows.push(row);
                    this.$nextTick(() => { const el = document.getElementById('prod-' + row.key); if (el) el.focus(); });
                },
                addFree() {
                    const row = mk({ is_free: true });
                    this.rows.push(row);
                    this.$nextTick(() => { const el = document.getElementById('name-' + row.key); if (el) el.focus(); });
                },
                canEdit(row) { return this.isAdmin || row.is_free; },
                remove(i) { if (this.rows.length > 1) this.rows.splice(i, 1); },

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
                setExtras(d) { this.extraT = d.t || 0; this.extraI = d.i || 0; },
                total() { return r2(this.subtotal() + this.tax() + this.extraT + this.extraI); },

                num(n, d = 2) { return Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: d }); },
                fmt(n) { return this.num(n, 2) + ' DH'; },
            };
        }
    </script>
</x-app-layout>