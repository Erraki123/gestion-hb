<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    @include('pdf._style')
</head>
<body>
    @include('pdf._header', ['title' => 'BON DE LIVRAISON', 'ref' => $delivery->displayNumber(), 'date' => $delivery->delivery_date ? $delivery->delivery_date->format('d/m/Y') : now()->format('d/m/Y')])

    @if ($delivery->status === 'brouillon')
        <div class="box"><span class="void">BROUILLON</span></div>
    @elseif ($delivery->status === 'annule')
        <div class="box"><span class="void">ANNULÉ</span></div>
    @endif

    <table class="info" style="width:100%; margin-top: 15px;">
        <tr>
            <td><strong>Client :</strong> {{ $delivery->client?->name ?? '—' }}</td>
            <td><strong>Commande :</strong> {{ $delivery->order?->number ?? '—' }}</td>
        </tr>
    </table>

    <table class="grid" style="margin-top: 18px;">
        <thead>
            <tr>
                <th>Désignation</th>
                <th>SKU</th>
                <th>Quantité</th>
                <th>Unité</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($delivery->items as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td>{{ $item->sku }}</td>
                <td class="right">{{ number_format((float) $item->quantity, 2, ',', ' ') }}</td>
                <td>{{ $item->unit }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="sign" style="width:100%; margin-top: 30px;">
        <tr>
            <td><div>Livré par</div><div style="margin-top: 50px; border-top: 1px solid #000; width: 60%; margin-left: auto; margin-right: auto;"></div></td>
            <td><div>Reçu par (nom, date, signature)</div><div style="margin-top: 50px; border-top: 1px solid #000; width: 60%; margin-left: auto; margin-right: auto;"></div></td>
        </tr>
    </table>
</body>
</html>
