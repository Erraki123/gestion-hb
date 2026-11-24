<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8">@include('pdf._style')</head>
<body>
    @include('pdf._header', ['title' => 'BON DE LIVRAISON', 'ref' => $delivery->displayNumber(), 'date' => $delivery->delivery_date ? $delivery->delivery_date->format('d/m/Y') : now()->format('d/m/Y')])

    <div class="page">
        @if ($delivery->status === 'brouillon')
            <div style="margin-top: 14px;"><span class="stamp stamp-draft">BROUILLON</span></div>
        @elseif ($delivery->status === 'annule')
            <div style="margin-top: 14px;"><span class="stamp stamp-late">ANNULÉ</span></div>
        @endif

        <table style="margin-top: 18px;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 8px;">
                    <div class="card"><div class="card-label">Client</div><strong>{{ $delivery->client?->name ?? '—' }}</strong></div>
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 8px;">
                    <div class="card"><div class="card-label">Commande</div>{{ $delivery->order?->number ?? '—' }}</div>
                </td>
            </tr>
        </table>

        <table class="grid">
            <thead>
                <tr><th>Désignation</th><th>SKU</th><th class="right">Quantité</th><th>Unité</th></tr>
            </thead>
            <tbody>
            @foreach ($delivery->items as $item)
                <tr class="{{ $loop->even ? 'alt' : '' }}">
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->sku }}</td>
                    <td class="right">{{ number_format((float) $item->quantity, 2, ',', ' ') }}</td>
                    <td>{{ $item->unit }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <table class="sign">
            <tr>
                <td>Livré par<div class="sign-line"></div></td>
                <td>Reçu par (nom, date, signature)<div class="sign-line"></div></td>
            </tr>
        </table>
    </div>
    @include('pdf._footer')
</body>
</html>
