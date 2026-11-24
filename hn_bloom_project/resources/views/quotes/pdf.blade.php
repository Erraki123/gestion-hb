<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8">@include('pdf._style')</head>
<body>
    @php
        $order     = $quote->order;
        $breakdown = \App\Services\DocumentTotals::breakdown($quote->items);
    @endphp

    @include('pdf._header', [
        'title' => $order ? 'BON DE COMMANDE' : 'DEVIS',
        'ref'   => $order ? $order->number : $quote->number,
        'date'  => $quote->issue_date->format('d/m/Y'),
    ])

    <div class="page">
        <table style="margin-top: 18px;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 8px;">
                    <div class="card">
                        <div class="card-label">Client</div>
                        <strong>{{ $quote->client?->name ?? '—' }}</strong><br>
                        {{ $quote->client?->address }}{{ $quote->client?->city ? ', ' . $quote->client->city : '' }}
                        @if ($quote->client?->ice) <br>ICE : {{ $quote->client->ice }} @endif
                    </div>
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 8px;">
                    <div class="card">
                        @if ($order)
                            <div class="card-label">Devis d'origine</div>
                            {{ $quote->number }}
                        @else
                            <div class="card-label">Valable jusqu'au</div>
                            {{ $quote->valid_until?->format('d/m/Y') ?? '—' }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <table class="grid">
            <thead>
                <tr>
                    <th>Désignation</th><th class="right">Qté</th><th class="right">Prix HT</th>
                    <th class="right">TVA</th><th class="right">Remise</th><th class="right">Total HT</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($quote->items as $item)
                <tr class="{{ $loop->even ? 'alt' : '' }}">
                    <td>{{ $item->name }}{{ $item->sku ? ' (' . $item->sku . ')' : '' }}</td>
                    <td class="right">{{ $item->quantity + 0 }} {{ $item->unit }}</td>
                    <td class="right">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                    <td class="right">{{ $item->tax_rate + 0 }} %</td>
                    <td class="right">{{ $item->discount_percent + 0 }} %</td>
                    <td class="right">{{ number_format($item->line_total_ht, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <table class="totals" style="width: 46%; margin-left: 54%; margin-top: 14px;">
            <tr><td>Total HT</td><td class="right">{{ number_format($quote->subtotal_ht, 2, ',', ' ') }} DH</td></tr>
            @foreach ($breakdown as $b)
                <tr><td>TVA {{ $b['rate'] + 0 }} %</td><td class="right">{{ number_format($b['tax'], 2, ',', ' ') }} DH</td></tr>
            @endforeach
            <tr class="ttc"><td>TOTAL TTC</td><td class="right">{{ number_format($quote->total_ttc, 2, ',', ' ') }} DH</td></tr>
        </table>

        @if ($quote->terms)
            <p style="margin-top: 20px;"><strong class="gold">Conditions</strong><br>{!! nl2br(e($quote->terms)) !!}</p>
        @endif

        @unless ($order)
            <table class="sign">
                <tr><td></td><td>Bon pour accord (nom, date, signature)<div class="sign-line"></div></td></tr>
            </table>
        @endunless
    </div>
    @include('pdf._footer')
</body>
</html>
