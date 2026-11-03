@csrf
<div x-data="quoteForm(@js($products), @js($initialItems), {{ auth()->user()->isAdmin() ? 'true' : 'false' }}, @js($mode), {{ $defaultRate }})">

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">
            @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    {{-- En-tête du devis --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <x-input-label for="client_id" value="Client *" />
            <select id="client_id" name="client_id" class="mt-1 block w-full" required>
                <option value="">— Choisir —</option>
                @foreach ($clients as $c)
                    <option value="{{ $c->id }}" @selected(old('client_id', $selectedClient) == $c->id)>
                        {{ $c->name }}{{ $c->company ? ' — ' . $c->company : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="issue_date" value="Date *" />
            <x-text-input id="issue_date" name="issue_date" type="date" class="mt-1 block w-full"
                :value="old('issue_date', optional($quote?->issue_date)->format('Y-m-d') ?? now()->format('Y-m-d'))" required />
        </div>
        <div>
            <x-input-label for="valid_until" value="Valable jusqu'au" />
            <x-text-input id="valid_until" name="valid_until" type="date" class="mt-1 block w-full"
                :value="old('valid_until', optional($quote?->valid_until)->format('Y-m-d') ?? now()->addDays(config('commerce.quote_validity_days'))->format('Y-m-d'))" />
        </div>
    </div>

    {{-- Lignes --}}
    <h3 class="panel-title mb-3">Produits</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-left">
                <tr>
                    <th class="p-2">Produit</th>
                    <th class="w-24 p-2">Qté</th>
                    <th class="w-36 p-2">Prix {{ $mode === 'ttc' ? 'TTC' : 'HT' }}</th>
                    <th class="w-24 p-2">TVA</th>
                    <th class="w-24 p-2">Remise %</th>
                    <th class="w-32 p-2 text-right">Total HT</th>
                    <th class="w-10 p-2"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row, i) in rows" :key="row.key">
                    <tr class="border-t border-white/5 align-top">
                        <td class="p-2">
                            <select :name="`items[${i}][product_id]`" x-model="row.product_id" @change="pick(row)" class="w-full" required>
                                <option value="">— Produit —</option>
                                <template x-for="p in products" :key="p.id">
                                    <option :value="p.id" x-text="`${p.name} (${p.sku}) — stock ${p.stock_quantity}`"></option>
                                </template>
                            </select>
                        </td>
                        <td class="p-2">
                            <input type="number" step="0.01" min="0.01" :name="`items[${i}][quantity]`" x-model.number="row.quantity" class="w-full" required>
                        </td>
                        <td class="p-2">
                            <input type="number" step="0.0001" min="0" x-model="row.price_input" @input="recalc(row)"
                                   :readonly="!isAdmin" :class="isAdmin ? '' : 'opacity-60'" class="w-full">
                            <input type="hidden" :name="`items[${i}][unit_price]`" :value="row.unit_price">
                            <div class="mt-1 text-[11px] text-gray-500"
                                 x-text="mode === 'ttc' ? 'HT ' + num(row.unit_price, 4) : 'TTC ' + num(row.unit_price * (1 + row.tax_rate / 100), 2)"></div>
                        </td>
                        <td class="p-2">
                            <select :name="isAdmin ? `items[${i}][tax_rate]` : null" :disabled="!isAdmin"
                                    x-model.number="row.tax_rate" @change="recalc(row)"
                                    :class="isAdmin ? '' : 'opacity-60'" class="w-full">
                                @foreach ($rates as $r)
                                    <option value="{{ $r + 0 }}">{{ $r + 0 }} %</option>
                                @endforeach
                            </select>
                            <input type="hidden" :name="!isAdmin ? `items[${i}][tax_rate]` : null" :value="row.tax_rate">
                        </td>
                        <td class="p-2">
                            <input type="number" step="0.01" min="0" max="100" :name="`items[${i}][discount_percent]`"
                                   x-model.number="row.discount_percent" class="w-full">
                        </td>
                        <td class="p-2 pt-4 text-right font-medium text-gray-900" x-text="fmt(lineHt(row))"></td>
                        <td class="p-2 pt-3">
                            <button type="button" @click="remove(i)" class="act act-danger" title="Supprimer la ligne">✕</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <button type="button" @click="add()" class="act mt-3">+ Ajouter une ligne</button>

    {{-- Totaux --}}
    <div class="mt-6 flex justify-end">
        <div class="w-full space-y-1 text-sm sm:w-80">
            <div class="flex justify-between"><span class="text-gray-500">Total HT</span><span class="text-gray-900" x-text="fmt(subtotal())"></span></div>
            <template x-for="b in breakdown()" :key="b.rate">
                <div class="flex justify-between text-gray-500">
                    <span>TVA <span x-text="b.rate"></span> % <span class="text-xs">(base <span x-text="fmt(b.base)"></span>)</span></span>
                    <span x-text="fmt(b.tax)"></span>
                </div>
            </template>
            <div class="flex justify-between border-t border-white/5 pt-2 text-base font-bold text-gray-900">
                <span>Total TTC</span><span x-text="fmt(total())"></span>
            </div>
        </div>
    </div>

    {{-- Conditions / notes --}}
    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="terms" value="Conditions" />
            <textarea id="terms" name="terms" rows="3" class="mt-1 block w-full">{{ old('terms', $quote->terms ?? '') }}</textarea>
        </div>
        <div>
            <x-input-label for="notes" value="Notes internes" />
            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full">{{ old('notes', $quote->notes ?? '') }}</textarea>
        </div>
    </div>

    <div class="mt-8 flex items-center justify-end gap-3 border-t border-white/5 pt-5">
        <a href="{{ route('quotes.index') }}" class="text-sm text-gray-500 hover:text-gray-900">Annuler</a>
        <button class="rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Enregistrer le devis</button>
    </div>
</div>

<script>
    function quoteForm(products, initial, isAdmin, mode, defaultRate) {
        let seq = 0;
        const r2 = (n) => Math.round((n + Number.EPSILON) * 100) / 100;
        const r4 = (n) => Math.round((n + Number.EPSILON) * 10000) / 10000;
        const shown = (ht, rate) => mode === 'ttc' ? r2(ht * (1 + rate / 100)) : r4(ht);

        const mk = (r = {}) => {
            const rate = parseFloat(r.tax_rate ?? defaultRate);
            const ht   = parseFloat(r.unit_price ?? 0) || 0;

            return {
                key: ++seq, product_id: r.product_id ?? '', quantity: r.quantity ?? 1,
                unit_price: ht, tax_rate: rate, discount_percent: r.discount_percent ?? 0,
                price_input: shown(ht, rate),
            };
        };

        return {
            products, isAdmin, mode,
            rows: initial.length ? initial.map(mk) : [mk()],
            add() { this.rows.push(mk()); },
            remove(i) { if (this.rows.length > 1) this.rows.splice(i, 1); },
            pick(row) {
                const p = this.products.find(p => p.id == row.product_id);
                row.tax_rate = p ? parseFloat(p.tax_rate) : defaultRate;
                row.unit_price = p ? r4(parseFloat(p.sale_price)) : 0;
                row.price_input = shown(row.unit_price, row.tax_rate);
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
            num(n, d = 2) { return n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: d }); },
            fmt(n) { return this.num(n, 2) + ' DH'; },
        };
    }
</script>
