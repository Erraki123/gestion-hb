<x-app-layout dark-header>
    <style>
        header, header > div { padding-top:.5rem !important; padding-bottom:.5rem !important; }
        .tbl { font-size:.8rem; }
        .tbl th { font-size:.68rem; padding:.45rem .7rem; }
        .tbl td { padding:.4rem .7rem; }
    </style>

    <x-slot name="header">
        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
            <a href="{{ route('products.index') }}" class="inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">← Retour</a>
            <div class="text-center">
                <h2 class="justify-center !text-lg !leading-tight">{{ $product->name }}</h2>
                <p class="text-xs text-gray-500">{{ $product->sku }}</p>
            </div>
            <div class="flex justify-end">
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('products.edit', $product) }}"
                       class="inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200">Modifier</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">
                @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif

        {{-- Fiche produit --}}
        <div class="panel flex flex-col gap-4 p-4 sm:flex-row">
            @if ($product->image_path)
                <img src="{{ asset('storage/' . $product->image_path) }}" class="h-32 w-full rounded-xl border border-white/10 object-cover sm:w-44" alt="">
            @endif

            <div class="grid flex-1 grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                @php
                    $facts = [
                        'Catégorie'    => $product->category?->name ?? '—',
                        'Prix de vente' => number_format($product->sale_price, 2, ',', ' ') . ' HT · ' . number_format($product->priceTtc(), 2, ',', ' ') . ' TTC',
                    ];
                    if (auth()->user()->isAdmin()) {
                        $facts['Prix d\'achat']    = number_format($product->purchase_price, 2, ',', ' ') . ' DH';
                        $facts['Marge unitaire']   = number_format($product->margin(), 2, ',', ' ') . ' DH';
                    }
                    $facts['TVA']            = ($product->tax_rate + 0) . ' %';
                    $facts['Seuil d\'alerte'] = ($product->min_stock + 0) . ' ' . $product->unit;
                @endphp
                @foreach ($facts as $k => $v)
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">{{ $k }}</div>
                        <div class="mt-0.5 font-medium text-gray-900">{{ $v }}</div>
                    </div>
                @endforeach
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Stock</div>
                    <span class="mt-0.5 inline-flex items-center gap-1.5 rounded px-2 py-1 text-sm font-semibold {{ $product->isLowStock() ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $product->isLowStock() ? 'bg-red-500' : 'bg-green-500' }}"></span>
                        {{ $product->stock_quantity + 0 }} {{ $product->unit }}
                    </span>
                </div>
                @if ($product->description)
                    <div class="col-span-full">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Description</div>
                        <div class="mt-0.5 text-gray-700">{{ $product->description }}</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Mouvement de stock : un seul bouton --}}
        @if (auth()->user()->isAdmin())
            <div class="panel p-4">
                <h3 class="panel-title mb-3"><span class="ico"><x-icon name="package" class="h-4 w-4" /></span> Changer le stock</h3>
                <form method="POST" action="{{ route('products.movements.store', $product) }}" class="grid grid-cols-1 gap-2 sm:grid-cols-4">
                    @csrf
                    <select name="type" class="!py-1.5 text-sm">
                        <option value="entree">Entrée (+)</option>
                        <option value="sortie">Sortie (−)</option>
                        <option value="ajustement">Ajustement (nouveau stock)</option>
                    </select>
                    <input type="number" step="0.01" min="0" name="quantity" placeholder="Quantité" required class="!py-1.5 text-sm">
                    <input type="text" name="reason" placeholder="Motif (facultatif)" class="!py-1.5 text-sm">
                    <button class="rounded-lg bg-gold-500 px-5 py-1.5 text-sm font-semibold text-gray-950 hover:bg-gold-400">Enregistrer</button>
                </form>
            </div>
        @endif

        {{-- Historique des mouvements (15 derniers) --}}
        <div class="panel overflow-hidden">
            <h3 class="panel-title p-4 pb-2"><span class="ico"><x-icon name="activity" class="h-4 w-4" /></span> Derniers mouvements</h3>
            <div class="overflow-x-auto">
                <table class="tbl min-w-full">
                    <thead class="text-left">
                        <tr><th>Date</th><th>Type</th><th>Quantité</th><th>Stock après</th><th>Motif</th><th>Par</th></tr>
                    </thead>
                    <tbody>
                    @forelse ($product->movements->take(15) as $m)
                        <tr>
                            <td>{{ $m->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ ucfirst($m->type) }}</td>
                            <td class="font-semibold {{ $m->quantity < 0 ? 'text-red-600' : 'text-green-600' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity + 0 }}</td>
                            <td>{{ $m->stock_after + 0 }}</td>
                            <td>{{ $m->reason }} {{ $m->reference ? '· ' . $m->reference : '' }}</td>
                            <td>{{ $m->user?->name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-gray-500">Aucun mouvement.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if (auth()->user()->isAdmin())
            {{-- Prix : replié par défaut pour alléger la page --}}
            <details class="panel overflow-hidden">
                <summary class="panel-title cursor-pointer p-4"><span class="ico"><x-icon name="percent" class="h-4 w-4" /></span> Historique des prix</summary>
                <div class="overflow-x-auto">
                    <table class="tbl min-w-full">
                        <thead class="text-left">
                            <tr><th>Date</th><th>Vente</th><th>Achat</th><th>Par</th></tr>
                        </thead>
                        <tbody>
                        @forelse ($product->priceHistories as $h)
                            <tr>
                                <td>{{ $h->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $h->old_sale_price }} → {{ $h->new_sale_price }}</td>
                                <td>{{ $h->old_purchase_price }} → {{ $h->new_purchase_price }}</td>
                                <td>{{ $h->user?->name }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-6 text-center text-gray-500">Aucun changement de prix.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </details>

            @include('activity._history', ['subject' => $product])
        @endif
    </div>
</x-app-layout>
