@csrf
@php
    $mode  = \App\Models\Setting::priceMode();
    $rates = \App\Models\Setting::taxRates();
    $rate  = (float) old('tax_rate', $product->tax_rate ?? config('commerce.tax_rate'));
    $price = old('sale_price_input', isset($product)
        ? \App\Support\Pricing::display((float) $product->sale_price, (float) $product->tax_rate) : 0);
@endphp

<div x-data="productPricing(@js($rate), @js((string) $price), @js($mode))" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="name" value="Nom du produit *" />
        <x-text-input id="name" name="name" class="block w-full" :value="old('name', $product->name ?? '')" required />
        <x-input-error :messages="$errors->get('name')" />
    </div>
    <div>
        <x-input-label for="sku" value="Référence SKU * (unique)" />
        <x-text-input id="sku" name="sku" class="block w-full" :value="old('sku', $product->sku ?? '')" required />
        <x-input-error :messages="$errors->get('sku')" />
    </div>
    <div>
        <x-input-label for="category_id" value="Catégorie" />
        <select id="category_id" name="category_id" class="block w-full border-gray-300 rounded">
            <option value="">— Aucune —</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id ?? '') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="unit" value="Unité *" />
        <x-text-input id="unit" name="unit" class="block w-full" :value="old('unit', $product->unit ?? 'pièce')" required />
    </div>

    <div>
        <x-input-label for="tax_rate" value="TVA du produit *" />
        <select id="tax_rate" name="tax_rate" x-model.number="rate" class="block w-full border-gray-300 rounded">
            @foreach ($rates as $r)
                <option value="{{ $r + 0 }}">{{ $r + 0 }} %</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('tax_rate')" />
    </div>
    <div>
        <x-input-label for="sale_price_input" :value="'Prix de vente ' . ($mode === 'ttc' ? 'TTC' : 'HT') . ' (DH) *'" />
        <x-text-input id="sale_price_input" name="sale_price_input" type="number" step="0.01" min="0"
                      x-model="price" class="block w-full" required />
        <x-input-error :messages="$errors->get('sale_price_input')" />
        <p class="text-xs text-gray-500 mt-1">Mode de saisie : {{ $mode === 'ttc' ? 'TTC' : 'HT' }} (modifiable dans Paramètres).</p>
    </div>

    <div class="sm:col-span-2 rounded border border-gold-200 bg-gold-50 p-3 text-sm text-gold-900">
        HT <strong x-text="n(ht)"></strong>
        + TVA (<span x-text="rate"></span> %) <strong x-text="n(tva)"></strong>
        = TTC <strong x-text="n(ttc)"></strong> DH
    </div>

    <div>
        <x-input-label for="purchase_price" value="Prix d'achat HT (DH) *" />
        <x-text-input id="purchase_price" name="purchase_price" type="number" step="0.0001" min="0" class="block w-full"
                      :value="old('purchase_price', isset($product) ? round((float) $product->purchase_price, 4) : 0)" required />
        <x-input-error :messages="$errors->get('purchase_price')" />
    </div>
    <div>
        <x-input-label for="min_stock" value="Seuil d'alerte stock *" />
        <x-text-input id="min_stock" name="min_stock" type="number" step="0.01" min="0" class="block w-full" :value="old('min_stock', $product->min_stock ?? 0)" required />
    </div>

    @unless (isset($product))
        <div>
            <x-input-label for="initial_stock" value="Stock initial" />
            <x-text-input id="initial_stock" name="initial_stock" type="number" step="0.01" min="0" class="block w-full" :value="old('initial_stock', 0)" />
        </div>
    @endunless

    <div class="sm:col-span-2">
        <x-input-label for="image" value="Image du produit" />
        <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="text-sm">
        <x-input-error :messages="$errors->get('image')" />
    </div>
    <div class="sm:col-span-2">
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" rows="3" class="block w-full border-gray-300 rounded">{{ old('description', $product->description ?? '') }}</textarea>
    </div>
    <div class="sm:col-span-2">
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))>
            <span class="text-sm">Produit actif (visible pour les commerciaux)</span>
        </label>
    </div>
</div>

<div class="mt-6"><x-primary-button>Enregistrer</x-primary-button></div>

<script>
    function productPricing(rate, price, mode) {
        return {
            rate, price, mode,
            get ht()  { const v = parseFloat(this.price) || 0; return this.mode === 'ttc' ? v / (1 + this.rate / 100) : v; },
            get tva() { return this.ht * this.rate / 100; },
            get ttc() { return this.ht + this.tva; },
            n(v) { return v.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 4 }); },
        };
    }
</script>

