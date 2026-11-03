<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2>Retour client {{ $customerReturn->displayNumber() }}</h2>
                <p class="mt-1 text-sm text-gray-500">Facture source : {{ $customerReturn->invoice?->displayNumber() ?? '—' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <x-status-badge :status="$customerReturn->status" />
                @if ($customerReturn->status === 'brouillon' && auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('returns.validate', $customerReturn) }}">
                        @csrf
                        <button type="submit" class="act">Valider le retour</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-green-200 bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="panel p-5">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Détails</h3>
                <dl class="space-y-3 text-sm text-gray-700">
                    <div class="flex justify-between gap-4"><dt>Client</dt><dd>{{ $customerReturn->client?->name }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Commercial</dt><dd>{{ $customerReturn->commercial?->name }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Date</dt><dd>{{ optional($customerReturn->return_date)->format('d/m/Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Motif</dt><dd>{{ $customerReturn->reason ?? '—' }}</dd></div>
                    @if ($customerReturn->creditNote)
                        <div class="flex justify-between gap-4"><dt>Avoir</dt><dd>{{ $customerReturn->creditNote->displayNumber() }}</dd></div>
                    @endif
                </dl>
            </div>

            <div class="panel p-5">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Notes</h3>
                <p class="text-sm text-gray-700">{{ $customerReturn->notes ?: 'Aucune note.' }}</p>
            </div>
        </div>

        @if ($customerReturn->status === 'brouillon')
            <div class="panel mt-6 p-5">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Modifier le retour</h3>
                <form method="POST" action="{{ route('returns.update', $customerReturn) }}" class="space-y-4">
                    @csrf @method('PUT')
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="return_date" class="mb-1 block text-sm text-gray-700">Date de retour</label>
                            <input id="return_date" type="date" name="return_date" value="{{ old('return_date', optional($customerReturn->return_date)->format('Y-m-d')) }}" class="w-full rounded border-gray-300">
                        </div>
                        <div>
                            <label for="reason" class="mb-1 block text-sm text-gray-700">Motif</label>
                            <select id="reason" name="reason" class="w-full rounded border-gray-300">
                                @foreach (['defectueux', 'erreur', 'surplus', 'autre'] as $reason)
                                    <option value="{{ $reason }}" @selected(old('reason', $customerReturn->reason) === $reason)>{{ ucfirst($reason) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="notes" class="mb-1 block text-sm text-gray-700">Notes</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full rounded border-gray-300">{{ old('notes', $customerReturn->notes) }}</textarea>
                    </div>
                    <div class="panel overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left">
                                <tr>
                                    <th class="p-4">Produit</th>
                                    <th class="p-4">Restant</th>
                                    <th class="p-4">Qté</th>
                                    <th class="p-4">Restock</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($customerReturn->items as $item)
                                <tr>
                                    <td class="p-4">{{ $item->name }}</td>
                                    <td class="p-4">{{ number_format((float) ($item->invoiceItem?->returnableQuantity() ?? 0), 2, ',', ' ') }}</td>
                                    <td class="p-4">
                                        <input type="number" min="0" step="0.01" name="quantity[{{ $item->id }}]" value="{{ old('quantity.' . $item->id, $item->quantity) }}" class="w-24 rounded border-gray-300" max="{{ (float) ($item->invoiceItem?->returnableQuantity() ?? 0) }}">
                                    </td>
                                    <td class="p-4">
                                        <input type="checkbox" name="restock[{{ $item->id }}]" value="1" @checked((bool) $item->restock)>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="act">Enregistrer</button>
                </form>
            </div>
        @endif

        <div class="panel mt-6 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="p-4">Produit</th>
                        <th class="p-4">Qté</th>
                        <th class="p-4">PU HT</th>
                        <th class="p-4">TVA</th>
                        <th class="p-4">Total HT</th>
                        <th class="p-4">Restock</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($customerReturn->items as $item)
                    <tr>
                        <td class="p-4">{{ $item->name }}</td>
                        <td class="p-4">{{ $item->quantity + 0 }}</td>
                        <td class="p-4">{{ number_format((float) $item->unit_price, 2, ',', ' ') }} DH</td>
                        <td class="p-4">{{ $item->tax_rate + 0 }} %</td>
                        <td class="p-4">{{ number_format((float) $item->line_total_ht, 2, ',', ' ') }} DH</td>
                        <td class="p-4">{{ $item->restock ? 'Oui' : 'Non' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-10 text-center text-gray-500">Aucune ligne sur ce retour.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>