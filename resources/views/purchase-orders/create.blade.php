@php
    $lbl = 'mb-1 block text-xs font-medium text-gray-600';
@endphp
<x-app-layout dark-header>
    <x-slot name="header">
        <x-purchase-head title="Nouvelle commande fournisseur" sub="Fournisseur + produits, c'est tout"
            :back="route('purchases.index', ['tab' => 'commandes'])" back-label="Achats" />
    </x-slot>

    <div class="py-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <x-purchase-flash />

        <form method="POST" action="{{ route('purchase-orders.store') }}" x-data="poForm(@js($products))" class="space-y-4">
            @csrf

            <div class="panel grid grid-cols-1 gap-3 p-4 sm:grid-cols-3">
                <div>
                    <label class="{{ $lbl }}">Fournisseur *</label>
                    <select name="supplier_id" required class="block w-full">
                        <option value="">— Choisir —</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected((string) old('supplier_id', $selectedSupplier) === (string) $s->id)>{{ $s->name }}{{ $s->company ? ' — ' . $s->company : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Date</label>
                    <input type="date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}" class="block w-full">
                </div>
                <div>
                    <label class="{{ $lbl }}">Livraison prévue (optionnel)</label>
                    <input type="date" name="expected_date" value="{{ old('expected_date') }}" class="block w-full">
                </div>
            </div>

            <div class="panel p-4">
                <div class="relative" x-on:click.outside="open = false">
                    <label class="{{ $lbl }}">Ajouter un produit</label>
                    <input type="text" x-model="q" x-on:focus="open = true" x-on:input="open = true"
                           x-on:keydown.enter.prevent="results[0] && add(results[0])"
                           placeholder="Tapez le nom ou la référence…" class="block w-full" autocomplete="off">
                    <div x-show="open && results.length" x-cloak
                         class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                        <template x-for="p in results" :key="p.id">
                            <button type="button" x-on:click="add(p)"
                                    class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-gold-50">
                                <span><span class="font-medium text-gray-900" x-text="p.name"></span>
                                    <span class="text-xs text-gray-500" x-text="p.sku"></span></span>
                                <span class="text-xs text-gray-600" x-text="fmt(p.purchase_price) + ' DH'"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="tight mt-3 overflow-x-auto" x-show="lines.length" x-cloak>
                    <table class="min-w-full">
                        <thead class="text-left">
                            <tr>
                                <th class="px-2 py-2">Produit</th>
                                <th class="px-2 py-2 w-24">Qté</th>
                                <th class="px-2 py-2 w-28">Prix HT</th>
                                <th class="px-2 py-2 w-20">Remise %</th>
                                <th class="px-2 py-2 w-20">TVA %</th>
                                <th class="px-2 py-2 text-right">Total HT</th>
                                <th class="px-2 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(l, i) in lines" :key="l.product_id">
                                <tr class="border-t border-gray-100">
                                    <td class="px-2 py-1">
                                        <input type="hidden" :name="'items[' + i + '][product_id]'" :value="l.product_id">
                                        <div class="font-medium text-gray-900" x-text="l.name"></div>
                                        <div class="text-xs text-gray-500" x-text="l.sku"></div>
                                    </td>
                                    <td class="px-2 py-1"><input type="number" step="any" min="0" x-model="l.quantity" :name="'items[' + i + '][quantity]'" class="w-full"></td>
                                    <td class="px-2 py-1"><input type="number" step="any" min="0" x-model="l.unit_cost" :name="'items[' + i + '][unit_cost]'" class="w-full"></td>
                                    <td class="px-2 py-1"><input type="number" step="any" min="0" max="100" x-model="l.discount_percent" :name="'items[' + i + '][discount_percent]'" class="w-full"></td>
                                    <td class="px-2 py-1"><input type="number" step="any" min="0" x-model="l.tax_rate" :name="'items[' + i + '][tax_rate]'" class="w-full"></td>
                                    <td class="px-2 py-1 text-right font-semibold whitespace-nowrap" x-text="fmt(ht(l)) + ' DH'"></td>
                                    <td class="px-2 py-1 text-right"><button type="button" class="act act-danger" x-on:click="remove(i)">✕</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-sm text-gray-500" x-show="!lines.length">Aucun produit ajouté pour le moment.</p>
            </div>

            <div class="panel flex flex-wrap items-end justify-between gap-4 p-4">
                <div class="min-w-[220px] flex-1">
                    <label class="{{ $lbl }}">Notes (optionnel)</label>
                    <input name="notes" value="{{ old('notes') }}" class="block w-full">
                </div>
                <div class="text-right text-sm text-gray-600">
                    <div>HT : <span x-text="fmt(subtotal)"></span> DH · TVA : <span x-text="fmt(tax)"></span> DH</div>
                    <div class="text-lg font-bold text-gray-900">Total TTC : <span x-text="fmt(total)"></span> DH</div>
                </div>
                <button class="inline-flex items-center justify-center rounded-lg bg-gold-500 px-5 py-2 text-sm font-semibold text-gray-950 hover:bg-gold-400 disabled:opacity-40"
                        :disabled="!lines.length">Enregistrer</button>
            </div>
        </form>
    </div>

    <script>
        function poForm(products) {
            return {
                products, q: '', open: false, lines: [],
                get results() {
                    const s = this.q.trim().toLowerCase();
                    return this.products.filter(p => !s || (p.name + ' ' + (p.sku || '')).toLowerCase().includes(s)).slice(0, 8);
                },
                add(p) {
                    const l = this.lines.find(x => x.product_id === p.id);
                    if (l) { l.quantity = (+l.quantity || 0) + 1; }
                    else { this.lines.push({ product_id: p.id, name: p.name, sku: p.sku, quantity: 1, unit_cost: p.purchase_price, discount_percent: 0, tax_rate: p.tax_rate }); }
                    this.q = ''; this.open = false;
                },
                remove(i) { this.lines.splice(i, 1); },
                ht(l) { return Math.round((+l.quantity || 0) * (+l.unit_cost || 0) * (1 - (+l.discount_percent || 0) / 100) * 100) / 100; },
                get subtotal() { return this.lines.reduce((s, l) => s + this.ht(l), 0); },
                get tax() {
                    const g = {};
                    this.lines.forEach(l => { const r = +l.tax_rate || 0; g[r] = (g[r] || 0) + this.ht(l); });
                    return Object.entries(g).reduce((s, [r, b]) => s + Math.round(b * r) / 100, 0);
                },
                get total() { return this.subtotal + this.tax; },
                fmt(n) { return (n || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            };
        }
    </script>
</x-app-layout>
