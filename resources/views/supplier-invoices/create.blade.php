@php
    $lbl = 'mb-1 block text-xs font-medium text-gray-600';
    $rates = collect($taxRates ?? [])
        ->map(fn ($r) => is_array($r) ? (float) ($r['rate'] ?? $r['value'] ?? 0) : (float) $r)
        ->push(20.0, 0.0)->unique()->sort()->values()->all();
    $supplierId = old('supplier_id', $selectedPo?->supplier_id ?? request('supplier_id'));
@endphp
<x-app-layout dark-header>
    <x-slot name="header">
        <x-purchase-head title="Facture fournisseur" sub="Saisissez les montants de la facture reçue"
            :back="route('purchases.index', ['tab' => 'factures'])" back-label="Achats" />
    </x-slot>

    <div class="py-5 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <x-purchase-flash />

        <form method="POST" action="{{ route('supplier-invoices.store') }}" x-data="invForm(@js($initialLines))" class="space-y-4">
            @csrf
            <div class="panel grid grid-cols-1 gap-3 p-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $lbl }}">Commande fournisseur (optionnel)</label>
                    <select name="purchase_order_id" class="block w-full"
                            onchange="window.location = '{{ route('supplier-invoices.create') }}' + (this.value ? '?purchase_order_id=' + this.value : '')">
                        <option value="">— Sans commande —</option>
                        @foreach ($purchaseOrders as $p)
                            <option value="{{ $p->id }}" @selected($selectedPo && $selectedPo->id === $p->id)>{{ $p->number ?: 'Brouillon #' . $p->id }} — {{ $p->supplier?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Fournisseur *</label>
                    <select name="supplier_id" required class="block w-full">
                        <option value="">— Choisir —</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected((string) $supplierId === (string) $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">N° de la facture du fournisseur *</label>
                    <input name="supplier_number" value="{{ old('supplier_number') }}" required maxlength="255" class="block w-full">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="{{ $lbl }}">Date *</label><input type="date" name="invoice_date" value="{{ old('invoice_date', now()->toDateString()) }}" required class="block w-full"></div>
                    <div><label class="{{ $lbl }}">Échéance</label><input type="date" name="due_date" value="{{ old('due_date') }}" class="block w-full"></div>
                </div>
                <p class="text-xs text-gray-500 sm:col-span-2">Échéance vide = calculée avec le délai de paiement du fournisseur.</p>
            </div>

            <div class="panel p-4">
                <div class="panel-title mb-2">Montants</div>
                <template x-for="(l, i) in lines" :key="i">
                    <div class="mb-2 flex flex-wrap items-end gap-3">
                        <div class="w-40">
                            <label class="{{ $lbl }}" x-show="i === 0">Montant HT</label>
                            <input type="number" step="0.01" min="0" x-model="l.base_ht" :name="'lines[' + i + '][base_ht]'" class="block w-full">
                        </div>
                        <div class="w-28">
                            <label class="{{ $lbl }}" x-show="i === 0">TVA</label>
                            <select x-model="l.tax_rate" :name="'lines[' + i + '][tax_rate]'" class="block w-full">
                                @foreach ($rates as $r)<option value="{{ $r }}">{{ rtrim(rtrim(number_format($r, 2, '.', ''), '0'), '.') }} %</option>@endforeach
                            </select>
                        </div>
                        <div class="pb-2 text-sm text-gray-600">TTC : <b x-text="fmt((+l.base_ht || 0) * (1 + (+l.tax_rate || 0) / 100))"></b> DH</div>
                        <button type="button" class="act act-danger mb-1" x-show="lines.length > 1" x-on:click="lines.splice(i, 1)">✕</button>
                    </div>
                </template>
                <button type="button" class="act" x-on:click="lines.push({ tax_rate: 20, base_ht: '' })">+ Autre taux de TVA</button>
            </div>

            <div class="panel flex flex-wrap items-end justify-between gap-4 p-4">
                <div class="min-w-[220px] flex-1">
                    <label class="{{ $lbl }}">Notes (optionnel)</label>
                    <input name="notes" value="{{ old('notes') }}" class="block w-full">
                </div>
                <div class="text-right text-sm text-gray-600">
                    <div>HT : <span x-text="fmt(ht)"></span> DH · TVA : <span x-text="fmt(tva)"></span> DH</div>
                    <div class="text-lg font-bold text-gray-900">Total TTC : <span x-text="fmt(ht + tva)"></span> DH</div>
                </div>
                <button class="inline-flex items-center justify-center rounded-lg bg-gold-500 px-5 py-2 text-sm font-semibold text-gray-950 hover:bg-gold-400">Enregistrer</button>
            </div>
        </form>
    </div>

    <script>
        function invForm(initial) {
            return {
                lines: initial.map(l => ({ tax_rate: l.tax_rate, base_ht: l.base_ht })),
                get ht() { return this.lines.reduce((s, l) => s + (+l.base_ht || 0), 0); },
                get tva() { return this.lines.reduce((s, l) => s + Math.round((+l.base_ht || 0) * (+l.tax_rate || 0)) / 100, 0); },
                fmt(n) { return (n || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            };
        }
    </script>
</x-app-layout>
