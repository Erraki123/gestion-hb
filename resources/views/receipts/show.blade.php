@php
    $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '—';
    $fmt  = fn ($n) => number_format((float) $n, 2, ',', ' ');
    $qty  = fn ($n) => rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.');
    $hbtn = 'inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200';
    $lbl  = 'mb-1 block text-xs font-medium text-gray-600';
    $draft = $receipt->isDraft();
@endphp
<x-app-layout dark-header>
    <x-slot name="header">
        <x-purchase-head :title="$receipt->number ?? 'Réception #' . $receipt->id"
            :sub="$receipt->supplier?->name"
            :back="route('purchase-orders.show', $receipt->purchaseOrder)" back-label="Commande">
            @if ($draft)
                <button type="button" class="{{ $hbtn }}" x-data
                        x-on:click="
                            const f = document.getElementById('receipt-form');
                            fetch(f.action, { method: 'POST', body: new FormData(f), headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                .then(() => document.getElementById('validate-form').submit())
                                .catch(() => alert('Enregistrement impossible, réessayez.'));
                        ">✓ Valider la réception</button>
            @endif
        </x-purchase-head>
    </x-slot>

    <div class="py-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <x-purchase-flash />

        <div class="panel flex flex-wrap items-center justify-between gap-3 p-4">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm text-gray-700">
                <x-purchase-badge :status="$receipt->status" type="receipt" />
                <span>Date : <b>{{ $date($receipt->receipt_date) }}</b></span>
                <span>Commande : <a class="font-semibold text-gray-900 hover:text-gold-700" href="{{ route('purchase-orders.show', $receipt->purchaseOrder) }}">{{ $receipt->purchaseOrder->number ?: '#' . $receipt->purchaseOrder->id }}</a></span>
                @if (! $draft && $receipt->supplier_ref)<span>Réf. fournisseur : <b>{{ $receipt->supplier_ref }}</b></span>@endif
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('receipts.pdf', $receipt) }}" target="_blank" class="{{ $hbtn }}">Télécharger Bon de réception PDF</a>
                @if ($draft)
                    <form method="POST" action="{{ route('receipts.destroy', $receipt) }}" onsubmit="return confirm('Supprimer cette réception ?')">
                        @csrf @method('DELETE') <button class="act act-danger">Supprimer</button>
                    </form>
                @elseif ($receipt->status === 'valide')
                    <form method="POST" action="{{ route('receipts.cancel', $receipt) }}"
                          onsubmit="const r = prompt('Raison de l’annulation ?'); if (!r || r.trim().length < 3) { return false; } this.reason.value = r; return true;">
                        @csrf <input type="hidden" name="reason" value="">
                        <button class="act act-danger">Annuler la réception</button>
                    </form>
                @endif
            </div>
        </div>

        @if ($draft)
            <form id="receipt-form" method="POST" action="{{ route('receipts.update', $receipt) }}" class="space-y-4">
                @csrf @method('PUT')
                <div class="panel grid grid-cols-1 gap-3 p-4 sm:grid-cols-3">
                    <div><label class="{{ $lbl }}">Date de réception</label><input type="date" name="receipt_date" value="{{ old('receipt_date', \Illuminate\Support\Carbon::parse($receipt->receipt_date)->format('Y-m-d')) }}" class="block w-full"></div>
                    <div><label class="{{ $lbl }}">Réf. bon du fournisseur</label><input name="supplier_ref" value="{{ old('supplier_ref', $receipt->supplier_ref) }}" class="block w-full"></div>
                    <div><label class="{{ $lbl }}">Notes</label><input name="notes" value="{{ old('notes', $receipt->notes) }}" class="block w-full"></div>
                </div>
                <div class="panel tight overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="text-left"><tr><th class="px-3 py-2">Produit</th><th class="px-3 py-2 w-32">Quantité reçue</th></tr></thead>
                        <tbody>
                            @foreach ($receipt->items as $it)
                                <tr class="border-t border-gray-100">
                                    <td class="px-3 py-2"><div class="font-medium text-gray-900">{{ $it->name }}</div>@if ($it->sku)<div class="text-xs text-gray-500">{{ $it->sku }}</div>@endif</td>
                                    <td class="px-3 py-2"><input type="number" step="any" min="0" name="items[{{ $it->id }}]" value="{{ $qty($it->quantity) }}" class="w-full"> <span class="text-xs text-gray-500">{{ $it->unit }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-500">Le bouton doré « Valider la réception » enregistre ces quantités puis met le stock à jour.</p>
            </form>
            <form id="validate-form" method="POST" action="{{ route('receipts.validate', $receipt) }}" class="hidden">@csrf</form>
        @else
            <div class="panel tight overflow-x-auto">
                <table class="min-w-full">
                    <thead class="text-left"><tr><th class="px-3 py-2">Produit</th><th class="px-3 py-2 text-right">Quantité</th><th class="px-3 py-2 text-right">Coût net</th></tr></thead>
                    <tbody>
                        @foreach ($receipt->items as $it)
                            <tr class="border-t border-gray-100">
                                <td class="px-3 py-2"><div class="font-medium text-gray-900">{{ $it->name }}</div>@if ($it->sku)<div class="text-xs text-gray-500">{{ $it->sku }}</div>@endif</td>
                                <td class="px-3 py-2 text-right whitespace-nowrap">{{ $qty($it->quantity) }} {{ $it->unit }}</td>
                                <td class="px-3 py-2 text-right whitespace-nowrap">{{ $fmt($it->net_unit_cost) }} DH</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($receipt->notes)<p class="text-sm text-gray-600">{{ $receipt->notes }}</p>@endif
        @endif
    </div>
</x-app-layout>
