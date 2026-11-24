<x-app-layout dark-header>
    <style>
        header, header > div { padding-top:.5rem !important; padding-bottom:.5rem !important; }
        .tbl { font-size:.8rem; }
        .tbl th { font-size:.68rem; padding:.45rem .7rem; }
        .tbl td { padding:.4rem .7rem; }
    </style>

    <x-slot name="header">
        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
            <div class="flex justify-start">
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('categories.index') }}" class="inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">Catégories</a>
                @endif
            </div>
            <div class="text-center">
                <h2 class="justify-center !text-lg !leading-tight">Produits</h2>
                <p id="count" class="text-xs text-gray-500">{{ $products->total() }} produit(s)</p>
            </div>
            <div class="flex justify-end">
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('products.create') }}"
                       class="inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">+ Nouveau produit</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="productList()">
        @if (session('success'))
            <div class="mb-3 rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-3 rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        {{-- Filtres : la liste se met à jour toute seule --}}
        <div class="panel mb-3 flex flex-wrap items-center gap-2 p-2.5">
            <div class="relative w-full sm:w-72">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" />
                <input type="text" x-model="q" @input.debounce.300ms="load()" placeholder="Nom ou SKU…" class="w-full pl-10 !py-1.5 text-sm">
            </div>
            <select x-model="category_id" @change="load()" class="!py-1.5 text-sm">
                <option value="">Toutes les catégories</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
            <select x-model="stock" @change="load()" class="!py-1.5 text-sm">
                <option value="">Tout le stock</option>
                <option value="low">Stock bas</option>
            </select>
            <span x-show="loading" x-cloak class="text-xs text-gray-500">Chargement…</span>
            <span x-show="failed" x-cloak class="text-xs text-red-600">Erreur, réessayez.</span>
        </div>

        <div class="panel overflow-x-auto" :class="loading ? 'opacity-60' : ''">
            <table class="tbl min-w-full">
                <thead class="text-left">
                    <tr>
                        <th>Produit</th>
                        <th>Catégorie</th>
                        <th>Prix de vente</th>
                        <th>Stock</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="rows">
                @forelse ($products as $p)
                    <tr>
                        <td>
                            <div class="flex items-center gap-2">
                                @if ($p->image_path)
                                    <img src="{{ asset('storage/' . $p->image_path) }}" class="h-8 w-8 rounded-lg border border-white/10 object-cover" alt="">
                                @else
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg border border-gold-500/30 bg-gold-500/10 text-gold-400"><x-icon name="package" class="h-4 w-4" /></span>
                                @endif
                                <div class="min-w-0">
                                    <a href="{{ route('products.show', $p) }}" class="block truncate font-semibold text-gray-900 hover:text-gold-400">{{ $p->name }}</a>
                                    <div class="text-[11px] text-gray-500">{{ $p->sku }}@unless ($p->is_active) · désactivé @endunless</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $p->category?->name ?? '—' }}</td>
                        <td>
                            {{ number_format($p->sale_price, 2, ',', ' ') }} HT
                            <div class="text-[11px] text-gray-500">{{ number_format($p->priceTtc(), 2, ',', ' ') }} TTC · TVA {{ $p->tax_rate + 0 }} %</div>
                        </td>
                        <td>
                            <span class="inline-flex items-center gap-1.5 rounded px-2 py-0.5 text-xs {{ $p->isLowStock() ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $p->isLowStock() ? 'bg-red-500' : 'bg-green-500' }}"></span>
                                {{ rtrim(rtrim(number_format($p->stock_quantity, 2, '.', ''), '0'), '.') }} {{ $p->unit }}
                            </span>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1.5">
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
                        <td colspan="5" class="p-10 text-center">
                            <x-icon name="package" class="mx-auto h-8 w-8 text-gray-400" />
                            <p class="mt-2 font-medium text-gray-900">Aucun produit</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div id="pager" class="mt-3"
             @click="const a = $event.target.closest('a'); if (a && a.href) { $event.preventDefault(); load(a.href); }">{{ $products->links() }}</div>
    </div>

    <script>
        function productList() {
            return {
                q: @js(request('q', '')),
                category_id: @js((string) request('category_id', '')),
                stock: @js(request('stock', '')),
                loading: false, failed: false,
                async load(url = null) {
                    if (!url) {
                        const p = new URLSearchParams();
                        if (this.q) p.set('q', this.q);
                        if (this.category_id) p.set('category_id', this.category_id);
                        if (this.stock) p.set('stock', this.stock);
                        url = location.pathname + (p.toString() ? '?' + p.toString() : '');
                    }
                    this.loading = true; this.failed = false;
                    try {
                        const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        if (!r.ok) throw new Error('http');
                        const d = new DOMParser().parseFromString(await r.text(), 'text/html');
                        document.getElementById('rows').innerHTML  = d.getElementById('rows').innerHTML;
                        document.getElementById('pager').innerHTML = d.getElementById('pager').innerHTML;
                        const c = d.getElementById('count');
                        if (c) document.getElementById('count').textContent = c.textContent;
                        history.replaceState(null, '', url);
                    } catch (e) { this.failed = true; }
                    this.loading = false;
                },
            };
        }
    </script>
</x-app-layout>
