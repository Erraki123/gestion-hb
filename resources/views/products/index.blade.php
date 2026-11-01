<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2>Produits</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $products->total() }} produit(s)</p>
            </div>
            @if (auth()->user()->isAdmin())
                <div class="flex gap-2">
                    <a href="{{ route('categories.index') }}" class="px-4 py-2.5 bg-gray-700 text-white rounded">Catégories</a>
                    <a href="{{ route('products.create') }}" class="px-4 py-2.5 bg-indigo-600 text-white rounded">+ Nouveau produit</a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <form method="GET" class="panel mb-5 flex flex-wrap items-center gap-3 p-3">
            <div class="relative w-full sm:w-72">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" />
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Nom ou SKU…" class="w-full pl-10">
            </div>
            <select name="category_id">
                <option value="">Toutes les catégories</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
            <select name="stock">
                <option value="">Tout le stock</option>
                <option value="low" @selected(request('stock') === 'low')>Stock bas</option>
            </select>
            <button class="rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Filtrer</button>
        </form>

        <div class="panel overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="p-4">Produit</th>
                        <th class="p-4">Catégorie</th>
                        <th class="p-4">Prix de vente</th>
                        <th class="p-4">Stock</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($products as $p)
                    <tr>
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                @if ($p->image_path)
                                    <img src="{{ asset('storage/' . $p->image_path) }}" class="h-11 w-11 rounded-xl border border-white/10 object-cover" alt="">
                                @else
                                    <span class="flex h-11 w-11 items-center justify-center rounded-xl border border-gold-500/30 bg-gold-500/10 text-gold-400"><x-icon name="package" class="h-5 w-5" /></span>
                                @endif
                                <div class="min-w-0">
                                    <a href="{{ route('products.show', $p) }}" class="block truncate font-semibold text-gray-900 hover:text-gold-400">{{ $p->name }}</a>
                                    <div class="text-xs text-gray-500">
                                        {{ $p->sku }}
                                        @unless ($p->is_active) · <span class="text-gray-400">désactivé</span> @endunless
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="p-4">{{ $p->category?->name ?? '—' }}</td>
                        <td class="p-4 font-medium text-gray-900">{{ number_format($p->sale_price, 2, ',', ' ') }} DH</td>
                        <td class="p-4">
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded text-xs {{ $p->isLowStock() ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $p->isLowStock() ? 'bg-red-500' : 'bg-green-500' }}"></span>
                                {{ rtrim(rtrim(number_format($p->stock_quantity, 2, '.', ''), '0'), '.') }} {{ $p->unit }}
                            </span>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('products.show', $p) }}" class="act">Voir</a>
                                @if (auth()->user()->isAdmin())
                                    <a href="{{ route('products.edit', $p) }}" class="act">Modifier</a>
                                    <form method="POST" action="{{ route('products.destroy', $p) }}" onsubmit="return confirm('Supprimer ce produit ?')">
                                        @csrf @method('DELETE')
                                        <button class="act act-danger">Supprimer</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-12 text-center">
                            <x-icon name="package" class="mx-auto h-10 w-10 text-gray-400" />
                            <p class="mt-3 font-medium text-gray-900">Aucun produit</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">{{ $products->links() }}</div>
    </div>
</x-app-layout>
