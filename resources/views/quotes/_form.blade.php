@csrf
<div x-data="quoteForm(@js($products), @js($initialItems), {{ $taxRate }}, {{ auth()->user()->isAdmin() ? 'true' : 'false' }})">

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">
            @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-3">
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

    <div class="overflow-hidden rounded-xl border border-white/5">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="p-3">Produit</th>
                        <th class="w-24 p-3">Qté</th>
                        <th class="w-32 p-3">Prix HT</th>
                        <th class="w-24 p-3">Remise %</th>
                        <th class="w-32 p-3 text-right">Total HT</th>
                        <th class="w-12 p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, i) in rows" :key="row.key">
                        <tr>
                            <td class="p-3">
                                <select :name="`items[${i}][product_id]`" x-model="row.product_id" @change="pick(row)" class="w-full" required>
                                    <option value="">— Produit —</option>
                                    <template x-for="p in products" :key="p.id">
                                        <option :value="p.id" x-text="`${p.name} (${p.sku}) — stock ${p.stock_quantity}`"></option>
                                    </template>
                                </select>
                            </td>
                            <td class="p-3"><input type="number" step="0.01" min="0.01" :name="`items[${i}][quantity]`" x-model.number="row.quantity" class="w-full" required></td>
                            <td class="p-3"><input type="number" step="0.01" min="0" :name="`items[${i}][unit_price]`" x-model.number="row.unit_price" :readonly="!isAdmin" :class="isAdmin ? '' : 'opacity-60'" class="w-full"></td>
                            <td class="p-3"><input type="number" step="0.01" min="0" max="100" :name="`items[${i}][discount_percent]`" x-model.number="row.discount_percent" class="w-full"></td>
                            <td class="p-3 text-right font-medium text-gray-900" x-text="fmt(lineTotal(row))"></td>
                            <td class="p-3"><button type="button" @click="remove(i)" class="act act-danger">✕</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <button type="button" @click="add()" class="act mt-4">+ Ajouter une ligne</button>

    <div class="mt-8 flex justify-end">
        <div class="w-full space-y-2 text-sm sm:w-80">
            <div class="flex justify-between text-gray-600"><span>Total HT</span><span x-text="fmt(subtotal())"></span></div>
            <div class="flex justify-between text-gray-600"><span>TVA (<span x-text="taxRate"></span>%)</span><span x-text="fmt(tax())"></span></div>
            <div class="flex justify-between border-t border-gold-500/40 pt-3 text-base font-bold text-gold-400"><span>Total TTC</span><span x-text="fmt(total())"></span></div>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2">
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
        <a href="{{ route('quotes.index') }}" class="text-sm text-gray-500 hover:text-white">Annuler</a>
        <x-primary-button>Enregistrer le devis</x-primary-button>
    </div>
</div>

<script>
    function quoteForm(products, initial, taxRate, isAdmin) {
        let seq = 0;
        const mk = (r = {}) => ({
            key: ++seq,
            product_id: r.product_id ?? '',
            quantity: r.quantity ?? 1,
            unit_price: r.unit_price ?? 0,
            discount_percent: r.discount_percent ?? 0,
        });
        return {
            products, taxRate, isAdmin,
            rows: initial.length ? initial.map(mk) : [mk()],
            add() { this.rows.push(mk()); },
            remove(i) { if (this.rows.length > 1) this.rows.splice(i, 1); },
            pick(row) {
                const p = this.products.find(p => p.id == row.product_id);
                row.unit_price = p ? parseFloat(p.sale_price) : 0;
            },
            lineTotal(r) { return (r.quantity || 0) * (r.unit_price || 0) * (1 - (r.discount_percent || 0) / 100); },
            subtotal() { return this.rows.reduce((s, r) => s + this.lineTotal(r), 0); },
            tax() { return this.subtotal() * this.taxRate / 100; },
            total() { return this.subtotal() + this.tax(); },
            fmt(n) { return n.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' DH'; },
        };
    }
</script>