@csrf
@php
    $mode     = \App\Models\Setting::priceMode();
    $rates    = \App\Models\Setting::taxRates();
    $isEdit   = isset($product);
    $rate     = (float) old('tax_rate', $product->tax_rate ?? config('commerce.tax_rate'));
    $price    = old('sale_price_input', $isEdit
        ? \App\Support\Pricing::display((float) $product->sale_price, (float) $product->tax_rate) : 0);
    $purchase = old('purchase_price', $isEdit ? round((float) $product->purchase_price, 4) : 0);
    $imageUrl = ($isEdit && $product->image_path) ? asset('storage/' . $product->image_path) : null;
    $back     = $isEdit ? route('products.show', $product) : route('products.index');
    $lbl      = 'mb-1 block text-sm font-medium text-gray-700';
    $card     = 'rounded-xl border border-gray-200 bg-white p-4';
    $inp      = 'mt-1 block w-full !py-1.5 text-sm';
@endphp

<div x-data="productPricing(@js($rate), @js((string) $price), @js($mode), @js((string) $purchase), @js($imageUrl))">

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">
            @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

        {{-- GAUCHE : LE PRODUIT --}}
        <section class="{{ $card }} space-y-3">
            <h3 class="text-sm font-bold text-gray-900">1. Le produit</h3>

            <div>
                <x-input-label for="name" value="Nom du produit *" />
                <x-text-input id="name" name="name" class="{{ $inp }}" :value="old('name', $product->name ?? '')" required autofocus />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <x-input-label for="sku" value="Référence (SKU) * — unique" />
                    <x-text-input id="sku" name="sku" class="{{ $inp }}" :value="old('sku', $product->sku ?? '')" required />
                    <x-input-error :messages="$errors->get('sku')" />
                </div>
                <div>
                    <x-input-label for="unit" value="Unité *" />
                    <x-text-input id="unit" name="unit" class="{{ $inp }}" :value="old('unit', $product->unit ?? 'pièce')" required />
                    <x-input-error :messages="$errors->get('unit')" />
                </div>
            </div>

            <div>
                <x-input-label for="category_id" value="Catégorie" />
                <select id="category_id" name="category_id" class="{{ $inp }}">
                    <option value="">— Aucune —</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id ?? '') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label for="description" value="Description" />
                <textarea id="description" name="description" rows="2" class="{{ $inp }}">{{ old('description', $product->description ?? '') }}</textarea>
            </div>

            <div>
                <p class="{{ $lbl }}">Image</p>
                <div class="flex items-center gap-3">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
                        <template x-if="preview"><img :src="preview" alt="" class="h-full w-full object-cover"></template>
                        <template x-if="!preview"><span class="px-1 text-center text-[10px] text-gray-400">Pas d'image</span></template>
                    </div>
                    <div>
                        <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="text-xs"
                               @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : {{ $imageUrl ? "'" . $imageUrl . "'" : 'null' }}">
                        <p class="mt-1 text-[11px] text-gray-500">JPG, PNG ou WEBP, 3 Mo max.</p>
                        <x-input-error :messages="$errors->get('image')" />
                    </div>
                </div>
            </div>
        </section>

        {{-- DROITE : PRIX ET STOCK --}}
        <section class="{{ $card }} space-y-3">
            <h3 class="text-sm font-bold text-gray-900">2. Prix et stock</h3>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <x-input-label for="tax_rate" value="TVA *" />
                    <select id="tax_rate" name="tax_rate" x-model.number="rate" class="{{ $inp }}">
                        @foreach ($rates as $r)
                            <option value="{{ $r + 0 }}">{{ $r + 0 }} %</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('tax_rate')" />
                </div>
                <div>
                    <x-input-label for="sale_price_input" :value="'Prix de vente ' . ($mode === 'ttc' ? 'TTC' : 'HT') . ' (DH) *'" />
                    <x-text-input id="sale_price_input" name="sale_price_input" type="number" step="0.01" min="0"
                                  x-model="price" class="{{ $inp }}" required />
                    <x-input-error :messages="$errors->get('sale_price_input')" />
                </div>
            </div>

            {{-- Résumé automatique --}}
            <div class="rounded-xl border border-gold-200 bg-gold-50 p-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-600">Prix HT</span><strong class="text-gray-900" x-text="n(ht) + ' DH'"></strong></div>
                <div class="mt-0.5 flex justify-between"><span class="text-gray-600">TVA (<span x-text="rate"></span> %)</span><strong class="text-gray-900" x-text="n(tva) + ' DH'"></strong></div>
                <div class="mt-1.5 flex items-baseline justify-between border-t border-gold-200 pt-1.5">
                    <span class="font-semibold text-gray-900">Prix TTC</span>
                    <strong class="text-lg text-gray-900" x-text="n(ttc) + ' DH'"></strong>
                </div>
            </div>

            <div>
                <x-input-label for="purchase_price" value="Prix d'achat HT (DH) *" />
                <x-text-input id="purchase_price" name="purchase_price" type="number" step="0.0001" min="0"
                              x-model="purchase" class="{{ $inp }}" required />
                <x-input-error :messages="$errors->get('purchase_price')" />
            </div>

            <div class="flex justify-between rounded-xl border border-gray-200 px-3 py-2 text-sm">
                <span class="text-gray-600">Marge par unité</span>
                <strong :class="margin < 0 ? 'text-red-600' : 'text-green-700'"
                        x-text="n(margin) + ' DH' + (ht > 0 ? '  (' + n(marginPct) + ' %)' : '')"></strong>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <x-input-label for="min_stock" value="Seuil d'alerte stock *" />
                    <x-text-input id="min_stock" name="min_stock" type="number" step="0.01" min="0" class="{{ $inp }}"
                                  :value="old('min_stock', $product->min_stock ?? 0)" required />
                    <x-input-error :messages="$errors->get('min_stock')" />
                </div>
                @unless ($isEdit)
                    <div>
                        <x-input-label for="initial_stock" value="Stock initial" />
                        <x-text-input id="initial_stock" name="initial_stock" type="number" step="0.01" min="0" class="{{ $inp }}"
                                      :value="old('initial_stock', 0)" />
                        <x-input-error :messages="$errors->get('initial_stock')" />
                    </div>
                @endunless
            </div>

            <label class="flex items-center gap-3 rounded-xl border border-gray-200 px-3 py-2">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))>
                <span class="text-sm text-gray-900">Produit actif <span class="text-xs text-gray-500">(décoché = caché dans les devis)</span></span>
            </label>
        </section>
    </div>

    {{-- Barre du bas : UN SEUL bouton --}}
    <div class="sticky bottom-0 -mx-4 -mb-4 mt-5 flex flex-wrap items-center justify-between gap-3 rounded-b-2xl border-t border-gray-200 bg-white/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:-mb-6 sm:px-6">
        <div class="text-sm text-gray-600">
            Prix TTC : <strong class="text-lg text-gray-900" x-text="n(ttc) + ' DH'"></strong>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ $back }}" class="text-sm text-gray-500 hover:text-gray-900">Annuler</a>
            <button class="rounded-lg bg-gold-500 px-8 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Enregistrer</button>
        </div>
    </div>
</div>

<script>
    function productPricing(rate, price, mode, purchase, image) {
        return {
            rate, price, mode, purchase, preview: image,
            get ht()        { const v = parseFloat(this.price) || 0; return this.mode === 'ttc' ? v / (1 + this.rate / 100) : v; },
            get tva()       { return this.ht * this.rate / 100; },
            get ttc()       { return this.ht + this.tva; },
            get margin()    { return this.ht - (parseFloat(this.purchase) || 0); },
            get marginPct() { return this.ht > 0 ? this.margin / this.ht * 100 : 0; },
            n(v) { return Number(v).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        };
    }
</script>
