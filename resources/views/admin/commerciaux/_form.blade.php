@csrf
@php
    $isEdit  = isset($commercial);
    $method0 = old('method', $current?->method ?? '');
    $value0  = old('value', $current ? ($current->value + 0) : '');

    // Taux spéciaux : anciennes valeurs (erreur de validation) sinon règle en vigueur
    $oldRates = old('product_rates');
    if (is_array($oldRates)) {
        $names = \App\Models\Product::withTrashed()->whereIn('id', collect($oldRates)->pluck('product_id'))->pluck('name', 'id');
        $rows  = collect($oldRates)->map(fn ($r) => [
            'product_id' => (int) $r['product_id'],
            'label'      => $names[(int) $r['product_id']] ?? ('Produit #' . $r['product_id']),
            'rate'       => $r['rate'],
        ])->values();
    } else {
        $rows = collect($rates ?? []);
    }
@endphp

<style>
    @media (min-width: 1024px) {
        .cm-form .panel { padding: .75rem 1rem; }
        .cm-form input:not([type=hidden]):not([type=checkbox]),
        .cm-form select { padding-top: .3rem; padding-bottom: .3rem; font-size: .82rem; }
        .cm-form label { font-size: .78rem; }
    }
</style>

<div class="cm-form space-y-4" x-data="commissionForm(@js((string) $method0), @js((string) $value0), @js($rows))">

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
            @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:items-start">

        {{-- COMPTE --}}
        <section class="panel p-5">
            <h3 class="panel-title mb-3">Compte</h3>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <x-input-label for="name" value="Nom complet *" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $commercial->name ?? '')" required />
                </div>
                <div>
                    <x-input-label for="phone" value="Téléphone" />
                    <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $commercial->phone ?? '')" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="email" value="Email (pour se connecter) *" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $commercial->email ?? '')" required />
                </div>
                <div>
                    <x-input-label for="password" :value="$isEdit ? 'Nouveau mot de passe' : 'Mot de passe *'" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" :required="! $isEdit" :placeholder="$isEdit ? 'Vide = inchangé' : ''" />
                </div>
                <div>
                    <x-input-label for="password_confirmation" value="Confirmer" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                </div>
            </div>
        </section>

        {{-- COMMISSION --}}
        <section class="panel p-5">
            <h3 class="panel-title mb-1">Commission</h3>
            <p class="mb-3 text-xs text-gray-500">
                @if ($current)
                    Actuelle depuis le {{ $current->starts_at->format('d/m/Y') }}. Si vous la changez, l’ancienne reste dans l’historique et les commissions déjà calculées ne bougent pas.
                @else
                    Aucune commission pour le moment.
                @endif
            </p>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="method" value="Calcul" />
                    <select id="method" name="method" x-model="method" class="mt-1 block w-full">
                        <option value="">Aucune commission</option>
                        @foreach (\App\Models\CommissionRule::METHODS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="method" x-cloak class="sm:col-span-2">
                    <label for="value" class="block text-sm font-medium text-gray-700" x-text="valueLabel()"></label>
                    <input id="value" name="value" type="number" step="0.01" min="0" x-model="value" :required="!!method" class="mt-1 block w-full">
                </div>
            </div>

            {{-- Exemple calculé --}}
            <p x-show="method" x-cloak class="mt-3 rounded-lg bg-gold-50 px-3 py-2 text-xs text-gray-700" x-text="example()"></p>

            {{-- Taux spéciaux par produit --}}
            <div x-show="method === 'product_percent'" x-cloak class="mt-4 border-t border-gray-200 pt-4">
                <h4 class="text-sm font-semibold text-gray-900">Taux spéciaux (facultatif)</h4>
                <p class="mb-2 text-xs text-gray-500">Ces produits ont leur propre taux. Les autres utilisent le taux général.</p>

                <div @picked="onProductPicked($event)">
                    <x-select-search class="max-w-md" name="_ajout_produit" :url="route('quotes.products.search')"
                                     placeholder="Ajouter un produit…" emptyText="Aucun produit trouvé" />
                </div>

                <div class="mt-3 overflow-x-auto" x-show="rows.length" x-cloak>
                    <table class="min-w-full text-sm">
                        <thead class="text-left">
                            <tr><th class="p-2">Produit</th><th class="w-32 p-2">Taux (%)</th><th class="w-10 p-2"></th></tr>
                        </thead>
                        <tbody>
                            <template x-for="(r, i) in rows" :key="r.key">
                                <tr class="border-t border-gray-200">
                                    <td class="p-2 text-gray-900">
                                        <span x-text="r.label"></span>
                                        <input type="hidden" :name="`product_rates[${i}][product_id]`" :value="r.product_id" :disabled="method !== 'product_percent'">
                                    </td>
                                    <td class="p-2">
                                        <input type="number" step="0.01" min="0" max="100" x-model="r.rate"
                                               :name="`product_rates[${i}][rate]`" :disabled="method !== 'product_percent'"
                                               :required="method === 'product_percent'" class="w-full">
                                    </td>
                                    <td class="p-2"><button type="button" @click="removeRate(i)" class="act act-danger" aria-label="Retirer ce produit">✕</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    {{-- HISTORIQUE (replié par défaut) --}}
    @if (isset($history) && $history->isNotEmpty())
        <details class="panel p-4">
            <summary class="panel-title cursor-pointer">Historique des règles ({{ $history->count() }})</summary>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left">
                        <tr><th class="p-2">Règle</th><th class="p-2">Du</th><th class="p-2">Au</th><th class="p-2">Défini par</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($history as $h)
                        <tr class="border-t border-gray-100">
                            <td class="p-2 text-gray-900">{{ $h->label() }}</td>
                            <td class="p-2 text-gray-700">{{ $h->starts_at->format('d/m/Y') }}</td>
                            <td class="p-2 text-gray-700">{{ $h->ends_at ? $h->ends_at->format('d/m/Y') : 'En vigueur' }}</td>
                            <td class="p-2 text-gray-700">{{ $h->creator?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif

    <div class="flex items-center justify-end gap-4">
        <a href="{{ route('admin.commerciaux.index') }}" class="text-sm text-gray-500 hover:text-gray-900">Annuler</a>
        <button type="submit" class="rounded-lg bg-gold-500 px-6 py-2 text-sm font-semibold text-gray-950 shadow-sm transition hover:bg-gold-400">Enregistrer</button>
    </div>
</div>

<script>
    function commissionForm(method, value, initialRows) {
        let seq = 0;
        const nf = (n) => Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        return {
            method, value,
            rows: initialRows.map(r => ({ key: ++seq, product_id: r.product_id, label: r.label, rate: r.rate })),

            valueLabel() {
                if (this.method === 'fixed_per_sale') return 'Montant par vente (DH)';
                if (this.method === 'product_percent') return 'Taux général (%) pour les produits sans taux spécial';
                return 'Taux (%)';
            },

            example() {
                const v = parseFloat(this.value);
                if (isNaN(v)) return 'Indiquez la valeur pour voir un exemple.';
                if (this.method === 'payments_percent') return `Exemple : ${nf(v)} % des paiements reçus. Un client paie 20 000 DH ⇒ commission ${nf(20000 * v / 100)} DH.`;
                if (this.method === 'invoice_percent')  return `Exemple : ${nf(v)} % du montant de la facture. Facture de 10 000 DH ⇒ commission ${nf(10000 * v / 100)} DH.`;
                if (this.method === 'fixed_per_sale')   return `Exemple : ${nf(v)} DH pour chaque facture entièrement encaissée.`;
                if (this.method === 'product_percent')  return `Exemple : taux général ${nf(v)} %. Facture de 1 000 DH sans taux spécial ⇒ commission ${nf(1000 * v / 100)} DH. Elle est calculée à chaque paiement reçu, avec le taux de chaque produit vendu.`;
                return '';
            },

            addRate(p) {
                if (this.rows.some(r => r.product_id == p.id)) return;
                this.rows.push({ key: ++seq, product_id: p.id, label: p.label, rate: this.value || '' });
            },
            removeRate(i) { this.rows.splice(i, 1); },

            // Un produit a été choisi dans la recherche : on l'ajoute puis on vide la recherche
            onProductPicked(e) {
                this.addRate(e.detail);
                const d = window.Alpine.$data(e.target);
                if (d) { d.query = ''; d.selectedId = ''; }
            },
        };
    }
</script>
