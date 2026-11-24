@php
    $fmt  = fn ($n) => number_format((float) $n, 2, ',', ' ');
    $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '—';
    $po   = $purchaseOrder;
    $st   = $po->status;

    $hbtn = 'inline-flex items-center gap-1 rounded-lg border border-gold-300 bg-gold-100 px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gold-200';
    $act  = 'act';
    $canInvoice = in_array($st, ['commande', 'recue_partielle', 'recue'], true);
@endphp
<x-app-layout dark-header>
    <x-slot name="header">
        <x-purchase-head :title="$po->number ?: 'Brouillon #' . $po->id" :sub="$po->supplier?->name"
            :back="route('purchases.index', ['tab' => 'commandes'])" back-label="Achats">
            @if ($st === 'brouillon')
                <form method="POST" action="{{ route('purchase-orders.confirm', $po) }}" onsubmit="return confirm('Confirmer cette commande ?')">
                    @csrf <button class="{{ $hbtn }}">✓ Confirmer la commande</button>
                </form>
            @elseif (in_array($st, ['commande', 'recue_partielle'], true))
                <form method="POST" action="{{ route('purchase-orders.receipts.store', $po) }}" onsubmit="return confirm('Recevoir toute la marchandise restante ?')">
                    @csrf <button class="{{ $hbtn }}">📦 Tout recevoir</button>
                </form>
            @elseif ($st === 'recue' && $invoices->isEmpty())
                <a href="{{ route('supplier-invoices.create', ['purchase_order_id' => $po->id]) }}" class="{{ $hbtn }}">Saisir la facture</a>
            @endif
        </x-purchase-head>
    </x-slot>

    <div class="py-5 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <x-purchase-flash />

        <div class="panel flex flex-wrap items-center justify-between gap-3 p-4">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm text-gray-700">
                <x-purchase-badge :status="$st" />
                <span>Date : <b>{{ $date($po->order_date) }}</b></span>
                <span>Prévue : <b>{{ $date($po->expected_date) }}</b></span>
                @if ($po->notes)<span class="text-gray-500">{{ $po->notes }}</span>@endif
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('purchase-orders.pdf', $po) }}" target="_blank" class="{{ $act }}">🖨 Imprimer</a>
                @if (in_array($st, ['commande', 'recue_partielle'], true))
                    <form method="POST" action="{{ route('purchase-orders.receipts.store', $po) }}">
                        @csrf <input type="hidden" name="partial" value="1">
                        <button class="{{ $act }}">Réception partielle</button>
                    </form>
                @endif
                @if ($canInvoice && ($st !== 'recue' || $invoices->isNotEmpty()))
                    <a href="{{ route('supplier-invoices.create', ['purchase_order_id' => $po->id]) }}" class="{{ $act }}">Saisir une facture</a>
                @endif
                @if ($st !== 'annulee')
                    <a href="{{ route('purchase-orders.edit', $po) }}" class="{{ $act }}">Modifier</a>
                @endif
                @if (in_array($st, ['brouillon', 'commande'], true))
                    <form method="POST" action="{{ route('purchase-orders.cancel', $po) }}" onsubmit="return confirm('Annuler cette commande ?')">
                        @csrf <button class="act act-danger">Annuler</button>
                    </form>
                @endif
                @if ($st === 'brouillon')
                    <form method="POST" action="{{ route('purchase-orders.destroy', $po) }}" onsubmit="return confirm('Supprimer ce brouillon ?')">
                        @csrf @method('DELETE') <button class="act act-danger">Supprimer</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="panel tight overflow-x-auto">
            <table class="min-w-full">
                <thead class="text-left">
                    <tr>
                        <th class="px-3 py-2">Produit</th>
                        <th class="px-3 py-2 text-right">Qté</th>
                        <th class="px-3 py-2 text-right">Reçu</th>
                        <th class="px-3 py-2 text-right">Prix HT</th>
                        <th class="px-3 py-2 text-right">Remise</th>
                        <th class="px-3 py-2 text-right">TVA</th>
                        <th class="px-3 py-2 text-right">Total HT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($po->items as $it)
                        @php $done = (float) $it->received_quantity >= (float) $it->quantity; @endphp
                        <tr class="border-t border-gray-100">
                            <td class="px-3 py-2">
                                <div class="font-medium text-gray-900">{{ $it->name }}</div>
                                @if ($it->sku)<div class="text-xs text-gray-500">{{ $it->sku }}</div>@endif
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">{{ rtrim(rtrim(number_format((float) $it->quantity, 3, '.', ''), '0'), '.') }} {{ $it->unit }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap {{ $done ? 'font-semibold text-green-700' : 'text-gray-700' }}">{{ rtrim(rtrim(number_format((float) $it->received_quantity, 3, '.', ''), '0'), '.') }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">{{ $fmt($it->unit_cost) }}</td>
                            <td class="px-3 py-2 text-right text-gray-600">{{ (float) $it->discount_percent > 0 ? rtrim(rtrim(number_format((float) $it->discount_percent, 2, '.', ''), '0'), '.') . ' %' : '—' }}</td>
                            <td class="px-3 py-2 text-right text-gray-600">{{ rtrim(rtrim(number_format((float) $it->tax_rate, 2, '.', ''), '0'), '.') }} %</td>
                            <td class="px-3 py-2 text-right font-semibold whitespace-nowrap">{{ $fmt($it->line_total_ht) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-200"><td colspan="6" class="px-3 py-1 text-right text-gray-600">Total HT</td><td class="px-3 py-1 text-right">{{ $fmt($po->subtotal_ht) }} DH</td></tr>
                    <tr><td colspan="6" class="px-3 py-1 text-right text-gray-600">TVA</td><td class="px-3 py-1 text-right">{{ $fmt($po->tax_amount) }} DH</td></tr>
                    <tr><td colspan="6" class="px-3 py-2 text-right font-semibold">Total TTC</td><td class="px-3 py-2 text-right text-base font-bold">{{ $fmt($po->total_ttc) }} DH</td></tr>
                </tfoot>
            </table>
        </div>

        @if ($po->receipts->isNotEmpty() || $invoices->isNotEmpty())
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @if ($po->receipts->isNotEmpty())
                    <div class="panel p-4">
                        <div class="panel-title mb-2">Réceptions</div>
                        @foreach ($po->receipts as $r)
                            <a href="{{ route('receipts.show', $r) }}" class="flex items-center justify-between border-t border-gray-100 py-2 text-sm first:border-0 hover:text-gold-700">
                                <span class="font-medium text-gray-900">{{ $r->number ?? 'Réception #' . $r->id }} · {{ $date($r->receipt_date) }}</span>
                                <x-purchase-badge :status="$r->status" type="receipt" />
                            </a>
                        @endforeach
                    </div>
                @endif
                @if ($invoices->isNotEmpty())
                    <div class="panel p-4">
                        <div class="panel-title mb-2">Factures fournisseur</div>
                        @foreach ($invoices as $inv)
                            <a href="{{ route('supplier-invoices.show', $inv) }}" class="flex items-center justify-between border-t border-gray-100 py-2 text-sm first:border-0 hover:text-gold-700">
                                <span class="font-medium text-gray-900">{{ $inv->supplier_number }} · {{ $fmt($inv->total_ttc) }} DH</span>
                                <x-purchase-badge :status="$inv->status" type="invoice" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
