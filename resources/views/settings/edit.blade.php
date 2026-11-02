<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Paramètres</h2></x-slot>

    <div class="py-8 max-w-4xl mx-auto sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
            @csrf @method('PUT')

            <div class="bg-white p-6 rounded shadow grid grid-cols-1 sm:grid-cols-2 gap-4">
                <h3 class="sm:col-span-2 font-semibold">Société</h3>
                <div><x-input-label for="company_name" value="Nom de la société *" />
                    <x-text-input id="company_name" name="company_name" class="block w-full" :value="old('company_name', $v['company_name'])" required /></div>
                <div><x-input-label for="company_ice" value="ICE" />
                    <x-text-input id="company_ice" name="company_ice" class="block w-full" :value="old('company_ice', $v['company_ice'])" /></div>
                <div class="sm:col-span-2"><x-input-label for="company_address" value="Adresse" />
                    <x-text-input id="company_address" name="company_address" class="block w-full" :value="old('company_address', $v['company_address'])" /></div>
                <div><x-input-label for="company_phone" value="Téléphone" />
                    <x-text-input id="company_phone" name="company_phone" class="block w-full" :value="old('company_phone', $v['company_phone'])" /></div>
                <div><x-input-label for="company_email" value="E-mail" />
                    <x-text-input id="company_email" name="company_email" type="email" class="block w-full" :value="old('company_email', $v['company_email'])" /></div>
            </div>

            <div class="bg-white p-6 rounded shadow grid grid-cols-1 sm:grid-cols-2 gap-4">
                <h3 class="sm:col-span-2 font-semibold">Facturation</h3>
                <div class="sm:col-span-2">
                    <x-input-label for="invoice_footer" value="Pied de page des factures (RC, IF, RIB, patente…)" />
                    <textarea id="invoice_footer" name="invoice_footer" rows="2" class="block w-full border-gray-300 rounded">{{ old('invoice_footer', $v['invoice_footer']) }}</textarea>
                </div>
                <div><x-input-label for="payment_days" value="Délai de paiement par défaut (jours) *" />
                    <x-text-input id="payment_days" name="payment_days" type="number" min="0" class="block w-full" :value="old('payment_days', $v['payment_days'])" required /></div>
                <div><x-input-label for="quote_validity_days" value="Validité d'un devis (jours) *" />
                    <x-text-input id="quote_validity_days" name="quote_validity_days" type="number" min="1" class="block w-full" :value="old('quote_validity_days', $v['quote_validity_days'])" required /></div>
            </div>

            <div class="bg-white p-6 rounded shadow grid grid-cols-1 sm:grid-cols-2 gap-4">
                <h3 class="sm:col-span-2 font-semibold">TVA et prix</h3>
                <p class="sm:col-span-2 text-xs text-gold-800 bg-gold-50 border border-gold-200 rounded p-2">
                    Les taux de TVA et les mentions légales doivent être validés avec votre comptable.
                </p>
                <div class="sm:col-span-2">
                    <x-input-label for="tax_rates" value="Taux de TVA autorisés (séparés par des virgules) *" />
                    <x-text-input id="tax_rates" name="tax_rates" class="block w-full" :value="old('tax_rates', $ratesText)" required />
                    <p class="text-xs text-gray-500 mt-1">Exemple : 20, 14, 10, 7, 0. Un taux utilisé par un produit ne peut pas être retiré.</p>
                </div>
                <div><x-input-label for="default_tax_rate" value="Taux par défaut (nouveaux produits) *" />
                    <x-text-input id="default_tax_rate" name="default_tax_rate" type="number" step="0.01" min="0" max="100" class="block w-full" :value="old('default_tax_rate', $v['default_tax_rate'] + 0)" required /></div>
                <div>
                    <x-input-label for="price_entry_mode" value="Saisie du prix de vente *" />
                    <select id="price_entry_mode" name="price_entry_mode" class="block w-full border-gray-300 rounded">
                        <option value="ht" @selected(old('price_entry_mode', $v['price_entry_mode']) === 'ht')>Prix HT : le logiciel calcule la TVA puis le TTC</option>
                        <option value="ttc" @selected(old('price_entry_mode', $v['price_entry_mode']) === 'ttc')>Prix TTC : le logiciel en déduit le HT et la TVA</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Changer ce mode ne modifie aucun prix existant. Le prix d'achat reste toujours en HT.</p>
                </div>
                <div>
                    <x-input-label for="stock_deduction" value="Moment de déduction du stock *" />
                    <select id="stock_deduction" name="stock_deduction" class="block w-full border-gray-300 rounded">
                        <option value="order" @selected(old('stock_deduction', $v['stock_deduction']) === 'order')>Commande confirmée : le stock quitte au moment de la confirmation</option>
                        <option value="delivery" @selected(old('stock_deduction', $v['stock_deduction']) === 'delivery')>Bon de livraison : le stock quitte lors de la validation du bon</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Le changement est sûr : chaque commande garde son propre indicateur de stock déjà déduit.</p>
                </div>
                <div>
                    <x-input-label for="purchase_cost_method" value="Méthode de coût d'achat *" />
                    <select id="purchase_cost_method" name="purchase_cost_method" class="block w-full border-gray-300 rounded">
                        <option value="average" @selected(old('purchase_cost_method', $v['purchase_cost_method']) === 'average')>Average : coût moyen pondéré</option>
                        <option value="last" @selected(old('purchase_cost_method', $v['purchase_cost_method']) === 'last')>Last : dernier coût d'achat</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Le coût moyen ou le dernier coût est utilisé lors de chaque réception fournisseur.</p>
                </div>
                <div>
                    <x-input-label for="commission_base" value="Base de calcul des commissions *" />
                    <select id="commission_base" name="commission_base" class="block w-full border-gray-300 rounded">
                        <option value="ttc" @selected(old('commission_base', $v['commission_base']) === 'ttc')>Montant TTC</option>
                        <option value="ht" @selected(old('commission_base', $v['commission_base']) === 'ht')>Montant HT</option>
                    </select>
                </div>
            </div>

            <x-primary-button>Enregistrer les paramètres</x-primary-button>
        </form>
    </div>
</x-app-layout>
