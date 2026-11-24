@php
    $fmt  = fn ($n) => number_format((float) $n, 2, ',', ' ');
    $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '—';
    $po   = $purchaseOrder->loadMissing(['supplier', 'items']);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Commande fournisseur {{ $po->number ?: '#' . $po->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; margin: 24px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #111; color: #e6c56a; text-align: left; padding: 6px; font-size: 11px; }
        td { padding: 6px; border-bottom: 1px solid #ddd; }
        .r { text-align: right; }
        .tot td { border: 0; padding: 3px 6px; }
        .bar { margin-bottom: 16px; }
        @media print { .bar { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="bar"><button onclick="window.print()">🖨 Imprimer</button></div>
    <div class="row">
        <div>
            <h1>BON DE COMMANDE</h1>
            <div>N° : <b>{{ $po->number ?: 'Brouillon #' . $po->id }}</b></div>
            <div>Date : {{ $date($po->order_date) }}</div>
            <div>Livraison prévue : {{ $date($po->expected_date) }}</div>
        </div>
        <div>
            <b>{{ $po->supplier?->name }}</b><br>
            @if ($po->supplier?->company){{ $po->supplier->company }}<br>@endif
            @if ($po->supplier?->address){{ $po->supplier->address }}<br>@endif
            @if ($po->supplier?->city){{ $po->supplier->city }}<br>@endif
            @if ($po->supplier?->phone)Tél : {{ $po->supplier->phone }}<br>@endif
            @if ($po->supplier?->ice)ICE : {{ $po->supplier->ice }}@endif
        </div>
    </div>
    <table>
        <thead>
            <tr><th>Produit</th><th class="r">Qté</th><th class="r">Prix HT</th><th class="r">Remise</th><th class="r">TVA</th><th class="r">Total HT</th></tr>
        </thead>
        <tbody>
            @foreach ($po->items as $it)
                <tr>
                    <td>{{ $it->name }}@if ($it->sku) <small>({{ $it->sku }})</small>@endif</td>
                    <td class="r">{{ rtrim(rtrim(number_format((float) $it->quantity, 3, '.', ''), '0'), '.') }} {{ $it->unit }}</td>
                    <td class="r">{{ $fmt($it->unit_cost) }}</td>
                    <td class="r">{{ (float) $it->discount_percent > 0 ? (float) $it->discount_percent . ' %' : '' }}</td>
                    <td class="r">{{ (float) $it->tax_rate }} %</td>
                    <td class="r">{{ $fmt($it->line_total_ht) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table class="tot" style="width:260px; margin-left:auto; margin-top:12px;">
        <tr><td>Total HT</td><td class="r">{{ $fmt($po->subtotal_ht) }} DH</td></tr>
        <tr><td>TVA</td><td class="r">{{ $fmt($po->tax_amount) }} DH</td></tr>
        <tr><td><b>Total TTC</b></td><td class="r"><b>{{ $fmt($po->total_ttc) }} DH</b></td></tr>
    </table>
    @if ($po->notes)<p><b>Notes :</b> {{ $po->notes }}</p>@endif
</body>
</html>
