@php
    $fmt  = fn ($n) => number_format((float) $n, 2, ',', ' ');
    $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '—';
    $inv  = $supplierInvoice;
    $left = max($inv->balance(), 0);
    $open = in_array($inv->status, ['non_payee', 'partiellement_payee'], true);
    $hasValidPayment = $inv->payments->where('status', 'valide')->isNotEmpty();
    $lbl  = 'mb-1 block text-xs font-medium text-gray-600';
@endphp
<x-app-layout dark-header>
    <x-slot name="header">
        <x-purchase-head :title="$inv->displayNumber()" :sub="$inv->supplier?->name"
            :back="route('purchases.index', ['tab' => 'factures'])" back-label="Achats" />
    </x-slot>

    <div class="py-5 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <x-purchase-flash />

        <div class="panel flex flex-wrap items-center justify-between gap-3 p-4">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm text-gray-700">
                <x-purchase-badge :status="$inv->status" type="invoice" />
                <span>Date : <b>{{ $date($inv->invoice_date) }}</b></span>
                <span>Échéance : <b class="{{ $inv->isOverdue() ? 'text-red-700' : '' }}">{{ $date($inv->due_date) }}</b></span>
                @if ($inv->purchaseOrder)
                    <span>Commande : <a class="font-semibold text-gray-900 hover:text-gold-700" href="{{ route('purchase-orders.show', $inv->purchaseOrder) }}">{{ $inv->purchaseOrder->number ?: '#' . $inv->purchaseOrder->id }}</a></span>
                @endif
                @if ($inv->notes)<span class="text-gray-500">{{ $inv->notes }}</span>@endif
            </div>
            @if ($inv->status === 'non_payee' && ! $hasValidPayment)
                <form method="POST" action="{{ route('supplier-invoices.cancel', $inv) }}" onsubmit="return confirm('Annuler cette facture ?')">
                    @csrf <button class="act act-danger">Annuler la facture</button>
                </form>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="panel tight p-4">
                <div class="panel-title mb-2">Montants</div>
                <table class="min-w-full">
                    <thead class="text-left"><tr><th class="py-1">TVA</th><th class="py-1 text-right">Base HT</th><th class="py-1 text-right">Montant TVA</th></tr></thead>
                    <tbody>
                        @foreach ($inv->lines as $l)
                            <tr class="border-t border-gray-100"><td class="py-1">{{ rtrim(rtrim(number_format((float) $l->tax_rate, 2, '.', ''), '0'), '.') }} %</td><td class="py-1 text-right">{{ $fmt($l->base_ht) }}</td><td class="py-1 text-right">{{ $fmt($l->tax_amount) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-3 space-y-1 border-t border-gray-200 pt-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-600">Total TTC</span><b>{{ $fmt($inv->total_ttc) }} DH</b></div>
                    <div class="flex justify-between"><span class="text-gray-600">Déjà payé</span><span>{{ $fmt($inv->amount_paid) }} DH</span></div>
                    <div class="flex justify-between text-base"><span class="font-semibold">Reste à payer</span><b class="{{ $left > 0 ? 'text-orange-600' : 'text-green-700' }}">{{ $fmt($left) }} DH</b></div>
                </div>
            </div>

            @if ($open && $left > 0)
                <div id="paiement" class="panel p-4" x-data="{ method: 'especes' }">
                    <div class="panel-title mb-2">Payer</div>
                    <form method="POST" action="{{ route('supplier-invoices.payments.store', $inv) }}" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="{{ $lbl }}">Montant (DH)</label><input type="number" step="0.01" min="0.01" max="{{ $left }}" name="amount" value="{{ old('amount', number_format($left, 2, '.', '')) }}" required class="block w-full"></div>
                            <div>
                                <label class="{{ $lbl }}">Mode</label>
                                <select name="method" x-model="method" class="block w-full">
                                    <option value="especes">Espèces</option>
                                    <option value="virement">Virement</option>
                                    <option value="cheque">Chèque</option>
                                </select>
                            </div>
                        </div>
                        <div x-show="method === 'cheque'" x-cloak><label class="{{ $lbl }}">N° du chèque</label><input name="cheque_number" class="block w-full"></div>
                        <div x-show="method !== 'especes'" x-cloak><label class="{{ $lbl }}">Référence (optionnel)</label><input name="reference" class="block w-full"></div>
                        <div class="flex justify-end">
                            <button class="inline-flex items-center justify-center rounded-lg bg-gold-500 px-5 py-2 text-sm font-semibold text-gray-950 hover:bg-gold-400">Enregistrer le paiement</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        @if ($inv->payments->isNotEmpty())
            <div class="panel tight overflow-x-auto">
                <table class="min-w-full">
                    <thead class="text-left"><tr><th class="px-3 py-2">N°</th><th class="px-3 py-2">Date</th><th class="px-3 py-2">Mode</th><th class="px-3 py-2 text-right">Montant</th><th class="px-3 py-2">Par</th><th class="px-3 py-2"></th></tr></thead>
                    <tbody>
                        @foreach ($inv->payments as $p)
                            @php $ok = $p->status === 'valide'; @endphp
                            <tr class="border-t border-gray-100 {{ $ok ? '' : 'text-gray-400 line-through' }}">
                                <td class="px-3 py-2 whitespace-nowrap">{{ $p->displayNumber() }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $date($p->paid_at) }}</td>
                                <td class="px-3 py-2">{{ ucfirst($p->method) }}{{ $p->cheque_number ? ' · ' . $p->cheque_number : '' }}</td>
                                <td class="px-3 py-2 text-right font-semibold whitespace-nowrap">{{ $fmt($p->amount) }} DH</td>
                                <td class="px-3 py-2">{{ $p->user?->name }}</td>
                                <td class="px-3 py-2 text-right">
                                    @if ($ok)
                                        <form method="POST" action="{{ route('supplier-payments.cancel', $p) }}"
                                              onsubmit="const r = prompt('Raison de l’annulation ?'); if (!r || r.trim().length < 3) { return false; } this.reason.value = r; return true;">
                                            @csrf <input type="hidden" name="reason" value="">
                                            <button class="act act-danger">Annuler</button>
                                        </form>
                                    @else
                                        <span class="text-xs no-underline">Annulé</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
