<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
    h1 { font-size: 20px; margin: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { border: 1px solid #ccc; padding: 6px; }
    th { background: #f0f0f0; text-align: left; }
    .right { text-align: right; }
    .box { width: 48%; display: inline-block; vertical-align: top; }
    .totals { width: 40%; margin-left: 60%; }
    .totals td { border: none; padding: 3px 6px; }
</style>
</head>
<body>
    <div class="box">
        <h1>{{ config('commerce.company.name') }}</h1>
        <div>{{ config('commerce.company.address') }}</div>
        <div>Tél : {{ config('commerce.company.phone') }}</div>
        <div>ICE : {{ config('commerce.company.ice') }}</div>
    </div>
    <div class="box right">
        <h1>DEVIS</h1>
        <div><strong>{{ $quote->number }}</strong></div>
        <div>Date : {{ $quote->issue_date->format('d/m/Y') }}</div>
        @if ($quote->valid_until) <div>Valable jusqu'au : {{ $quote->valid_until->format('d/m/Y') }}</div> @endif
    </div>

    <p style="margin-top:20px">
        <strong>Client :</strong> {{ $quote->client->name }}<br>
        @if ($quote->client->company) {{ $quote->client->company }}<br> @endif
        {{ $quote->client->address }} {{ $quote->client->city }}<br>
        @if ($quote->client->ice) ICE : {{ $quote->client->ice }} @endif
    </p>

    <table>
        <thead>
            <tr><th>Désignation</th><th>Qté</th><th>Prix HT</th><th>Remise</th><th class="right">Total HT</th></tr>
        </thead>
        <tbody>
        @foreach ($quote->items as $i)
            <tr>
                <td>{{ $i->name }}<br><small>{{ $i->sku }}</small></td>
                <td>{{ $i->quantity + 0 }} {{ $i->unit }}</td>
                <td class="right">{{ number_format($i->unit_price, 2, ',', ' ') }}</td>
                <td>{{ $i->discount_percent + 0 }} %</td>
                <td class="right">{{ number_format($i->line_total_ht, 2, ',', ' ') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Total HT</td><td class="right">{{ number_format($quote->subtotal_ht, 2, ',', ' ') }} DH</td></tr>
        <tr><td>TVA ({{ $quote->tax_rate + 0 }}%)</td><td class="right">{{ number_format($quote->tax_amount, 2, ',', ' ') }} DH</td></tr>
        <tr><td><strong>Total TTC</strong></td><td class="right"><strong>{{ number_format($quote->total_ttc, 2, ',', ' ') }} DH</strong></td></tr>
    </table>

    @if ($quote->terms)
        <p><strong>Conditions :</strong><br>{!! nl2br(e($quote->terms)) !!}</p>
    @endif
</body>
</html>