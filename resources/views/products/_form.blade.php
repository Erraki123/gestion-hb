@csrf
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div>
        <x-input-label for="name" value="Nom du produit *" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $product->name ?? '')" required />
        <x-input-error :messages="$errors->get('name')" />
    </div>
    <div>
        <x-input-label for="sku" value="Référence SKU * (unique)" />
        <x-text-input id="sku" name="sku" class="mt-1 block w-full" :value="old('sku', $product->sku ?? '')" required />
        <x-input-error :messages="$errors->get('sku')" />
    </div>
    <div>
        <x-input-label for="category_id" value="Catégorie" />
        <select id="category_id" name="category_id" class="mt-1 block w-full">
            <option value="">— Aucune —</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id ?? '') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="unit" value="Unité *" />
        <x-text-input id="unit" name="unit" class="mt-1 block w-full" :value="old('unit', $product->unit ?? 'pièce')" required />
    </div>
    <div>
        <x-input-label for="purchase_price" value="Prix d'achat (DH) *" />
        <x-text-input id="purchase_price" name="purchase_price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('purchase_price', $product->purchase_price ?? 0)" required />
        <x-input-error :messages="$errors->get('purchase_price')" />
    </div>
    <div>
        <x-input-label for="sale_price" value="Prix de vente (DH) *" />
        <x-text-input id="sale_price" name="sale_price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('sale_price', $product->sale_price ?? 0)" required />
        <x-input-error :messages="$errors->get('sale_price')" />
    </div>
    <div>
        <x-input-label for="min_stock" value="Seuil d'alerte stock *" />
        <x-text-input id="min_stock" name="min_stock" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('min_stock', $product->min_stock ?? 0)" required />
    </div>

    @unless (isset($product))
        <div>
            <x-input-label for="initial_stock" value="Stock initial" />
            <x-text-input id="initial_stock" name="initial_stock" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('initial_stock', 0)" />
        </div>
    @endunless

    <div class="sm:col-span-2">
        <x-input-label for="image" value="Image du produit" />
        <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="mt-1 text-sm">
        <x-input-error :messages="$errors->get('image')" />
    </div>
    <div class="sm:col-span-2">
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full">{{ old('description', $product->description ?? '') }}</textarea>
    </div>
    <div class="sm:col-span-2">
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))>
            <span class="text-sm">Produit actif (visible pour les commerciaux)</span>
        </label>
    </div>
</div>
<div class="mt-8 flex items-center justify-end gap-3 border-t border-white/5 pt-5">
    <a href="{{ route('products.index') }}" class="text-sm text-gray-500 hover:text-white">Annuler</a>
    <x-primary-button>Enregistrer</x-primary-button>
</div>
