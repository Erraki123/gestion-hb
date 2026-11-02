<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2>{{ $delivery->displayNumber() }} <x-status-badge :status="$delivery->status" /></h2>
                <p class="mt-1 text-sm text-gray-500">Bon de livraison · {{ $delivery->client?->name ?? '—' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('deliveries.pdf', $delivery) }}" target="_blank" class="px-3 py-2 bg-gray-700 text-white rounded">PDF</a>
                @if ($delivery->isDraft())
                    <form method="POST" action="{{ route('deliveries.validate', $delivery) }}" onsubmit="return confirm('Valider ce bon de livraison ?')">
                        @csrf
                        <button class="px-3 py-2 bg-green-600 text-white rounded">Valider</button>
                    </form>
                @endif
                @if (auth()->user()->isAdmin() && $delivery->status === 'valide')
                    <form method="POST" action="{{ route('deliveries.cancel', $delivery) }}" onsubmit="return confirm('Annuler ce bon de livraison ?')">
                        @csrf
                        <input type="text" name="reason" value="{{ old('reason') }}" placeholder="Motif de l’annulation" class="rounded border border-gray-300 px-3 py-2 text-sm" required>
                        <button class="px-3 py-2 bg-red-600 text-white rounded">Annuler</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-100 p-3 text-sm text-red-800">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <div class="panel grid grid-cols-1 gap-5 p-6 sm:grid-cols-3">
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Commande</div>
                <div class="mt-1 text-sm font-medium text-gray-900"><a class="text-indigo-600" href="{{ route('orders.show', $delivery->order) }}">{{ $delivery->order?->number ?? '—' }}</a></div>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Client</div>
                <div class="mt-1 text-sm font-medium text-gray-900">{{ $delivery->client?->name ?? '—' }}</div>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Commercial</div>
                <div class="mt-1 text-sm font-medium text-gray-900">{{ $delivery->commercial?->name ?? '—' }}</div>
            </div>
        </div>

        @if ($delivery->isDraft())
            <form method="POST" action="{{ route('deliveries.update', $delivery) }}" onsubmit="return confirm('Mettre à jour ce bon de livraison ?')" class="panel p-6">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <x-input-label for="delivery_date" value="Date de livraison" />
                        <x-text-input id="delivery_date" name="delivery_date" type="date" class="block w-full" :value="old('delivery_date', optional($delivery->delivery_date)->format('Y-m-d'))" />
                    </div>
                    <div>
                        <x-input-label for="received_by" value="Réceptionné par" />
                        <x-text-input id="received_by" name="received_by" class="block w-full" :value="old('received_by', $delivery->received_by)" />
                    </div>
                    <div>
                        <x-input-label for="notes" value="Notes" />
                        <x-text-input id="notes" name="notes" class="block w-full" :value="old('notes', $delivery->notes)" />
                    </div>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left">
                            <tr>
                                <th class="p-3">Désignation</th>
                                <th class="p-3">SKU</th>
                                <th class="p-3">Quantité</th>
                                <th class="p-3">Reste</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($delivery->items as $item)
                            @php $remaining = $item->orderItem ? (float) $item->orderItem->remainingQuantity() : 0; @endphp
                            <tr>
                                <td class="p-3">{{ $item->name }}</td>
                                <td class="p-3">{{ $item->sku }}</td>
                                <td class="p-3">
                                    <input type="number" step="0.01" min="0" max="{{ $remaining + (float) $item->quantity }}" name="items[{{ $item->id }}]" value="{{ old('items.' . $item->id, $item->quantity) }}" class="w-24 border-gray-300 rounded">
                                </td>
                                <td class="p-3 text-gray-600">{{ number_format($remaining, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-between gap-3">
                    <button class="px-3 py-2 bg-gold-500 text-gray-950 rounded" type="submit">Enregistrer</button>
                </div>
            </form>

            <form method="POST" action="{{ route('deliveries.destroy', $delivery) }}" onsubmit="return confirm('Supprimer ce brouillon ?')" class="mt-3">
                @csrf @method('DELETE')
                <button type="submit" class="px-3 py-2 bg-red-600 text-white rounded">Supprimer</button>
            </form>
        @else
            <div class="panel p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <div class="text-[11px] uppercase tracking-wider text-gray-500">Date</div>
                        <div class="mt-1 text-sm font-medium text-gray-900">{{ $delivery->delivery_date ? $delivery->delivery_date->format('d/m/Y') : '—' }}</div>
                    </div>
                    <div>
                        <div class="text-[11px] uppercase tracking-wider text-gray-500">Réceptionné par</div>
                        <div class="mt-1 text-sm font-medium text-gray-900">{{ $delivery->received_by ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-[11px] uppercase tracking-wider text-gray-500">Notes</div>
                        <div class="mt-1 text-sm font-medium text-gray-900">{{ $delivery->notes ?? '—' }}</div>
                    </div>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left">
                            <tr>
                                <th class="p-3">Désignation</th>
                                <th class="p-3">SKU</th>
                                <th class="p-3">Quantité</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($delivery->items as $item)
                            <tr>
                                <td class="p-3">{{ $item->name }}</td>
                                <td class="p-3">{{ $item->sku }}</td>
                                <td class="p-3">{{ number_format($item->quantity, 2, ',', ' ') }} {{ $item->unit }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
