<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2>{{ $product->name }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $product->sku }}</p>
            </div>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('products.edit', $product) }}" class="px-4 py-2.5 bg-yellow-500 text-white rounded">Modifier</a>
            @endif
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">
                @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif

        <div class="panel flex flex-col gap-6 p-6 sm:flex-row">
            @if ($product->image_path)
                <img src="{{ asset('storage/' . $product->image_path) }}" class="h-40 w-full rounded-xl border border-white/10 object-cover sm:w-56" alt="">
            @endif

            <div class="grid flex-1 grid-cols-2 gap-5 text-sm sm:grid-cols-3">
                @php
                    $facts = [
                        'Catégorie' => $product->category?->name ?? '—',
                    ];
                    if (auth()->user()->isAdmin()) {
                        $facts['Prix d\'achat'] = number_format($product->purchase_price, 2, ',', ' ') . ' DH';
                        $facts['Marge unitaire'] = number_format($product->margin(), 2, ',', ' ') . ' DH';
                    }
                    $facts['Seuil d\'alerte'] = $product->min_stock + 0;
                @endphp
                @foreach ($facts as $k => $v)
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">{{ $k }}</div>
                        <div class="mt-1 font-medium text-gray-900">{{ $v }}</div>
                    </div>
                @endforeach
                <div class="col-span-full">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Prix de vente</div>
                    <div class="mt-1 font-medium text-gray-900">
                        {{ number_format($product->sale_price, 2, ',', ' ') }} HT · {{ number_format($product->priceTtc(), 2, ',', ' ') }} TTC
                        (TVA {{ $product->tax_rate + 0 }} %)
                    </div>
                </div>
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Stock</div>
                    <span class="mt-1 inline-flex items-center gap-1.5 px-2 py-1 rounded text-xs {{ $product->isLowStock() ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $product->isLowStock() ? 'bg-red-500' : 'bg-green-500' }}"></span>
                        {{ $product->stock_quantity + 0 }} {{ $product->unit }}
                    </span>
                </div>
                <div class="col-span-full">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Description</div>
                    <div class="mt-1 text-gray-700">{{ $product->description ?: '—' }}</div>
                </div>
            </div>
        </div>

        @if (auth()->user()->isAdmin())
            <div class="panel p-6">
                <h3 class="panel-title mb-4"><span class="ico"><x-icon name="package" class="h-4 w-4" /></span> Mouvement de stock</h3>
                <form method="POST" action="{{ route('products.movements.store', $product) }}" class="grid grid-cols-1 gap-3 sm:grid-cols-5">
                    @csrf
                    <select name="type">
                        <option value="entree">Entrée (+)</option>
                        <option value="sortie">Sortie (−)</option>
                        <option value="ajustement">Ajustement (nouveau stock)</option>
                    </select>
                    <input type="number" step="0.01" min="0" name="quantity" placeholder="Quantité" required>
                    <input type="text" name="reason" placeholder="Motif">
                    <input type="text" name="reference" placeholder="Référence">
                    <x-primary-button>Valider</x-primary-button>
                </form>
            </div>
        @endif

        <div class="panel overflow-hidden">
            <h3 class="panel-title p-5 pb-3"><span class="ico"><x-icon name="activity" class="h-4 w-4" /></span> Historique des mouvements</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left">
                        <tr>
                            <th class="p-4">Date</th><th class="p-4">Type</th><th class="p-4">Quantité</th>
                            <th class="p-4">Stock après</th><th class="p-4">Motif</th><th class="p-4">Par</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($product->movements as $m)
                        <tr>
                            <td class="p-4">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                            <td class="p-4">{{ ucfirst($m->type) }}</td>
                            <td class="p-4 font-semibold {{ $m->quantity < 0 ? 'text-red-600' : 'text-green-600' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity + 0 }}</td>
                            <td class="p-4">{{ $m->stock_after + 0 }}</td>
                            <td class="p-4">{{ $m->reason }} {{ $m->reference ? '· ' . $m->reference : '' }}</td>
                            <td class="p-4">{{ $m->user?->name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-10 text-center text-gray-500">Aucun mouvement.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if (auth()->user()->isAdmin())
            <div class="panel overflow-hidden">
                <h3 class="panel-title p-5 pb-3"><span class="ico"><x-icon name="percent" class="h-4 w-4" /></span> Historique des prix</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left">
                            <tr><th class="p-4">Date</th><th class="p-4">Vente</th><th class="p-4">Achat</th><th class="p-4">Par</th></tr>
                        </thead>
                        <tbody>
                        @forelse ($product->priceHistories as $h)
                            <tr>
                                <td class="p-4">{{ $h->created_at->format('d/m/Y H:i') }}</td>
                                <td class="p-4">{{ $h->old_sale_price }} → {{ $h->new_sale_price }}</td>
                                <td class="p-4">{{ $h->old_purchase_price }} → {{ $h->new_purchase_price }}</td>
                                <td class="p-4">{{ $h->user?->name }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-10 text-center text-gray-500">Aucun changement de prix.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @include('activity._history', ['subject' => $product])
        @endif
    </div>
</x-app-layout>