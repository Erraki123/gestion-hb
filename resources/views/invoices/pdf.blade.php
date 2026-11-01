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
    .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 9px; text-align: center; color: #666; border-top: 1px solid #ccc; padding-top: 4px; }
    .draft { color: #c00; font-weight: bold; }
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
        <h1>{{ strtoupper($invoice->title()) }}</h1>
        <div><strong>{{ $invoice->displayNumber() }}</strong></div>
        @if ($invoice->isDraft()) <div class="draft">BROUILLON</div> @endif
        <div>Date : {{ $invoice->issue_date->format('d/m/Y') }}</div>
        @if ($invoice->due_date) <div>Échéance : {{ $invoice->due_date->format('d/m/Y') }}</div> @endif
        @if ($invoice->parent) <div>Annule la facture {{ $invoice->parent->number }}</div> @endif
    </div>

    <p style="margin-top:20px">
        <strong>Client :</strong> {{ $invoice->client->name }}<br>
        @if ($invoice->client->company) {{ $invoice->client->company }}<br> @endif
        {{ $invoice->client->address }} {{ $invoice->client->city }}<br>
        @if ($invoice->client->ice) ICE : {{ $invoice->client->ice }} @endif
    </p>

    <table>
        <thead>
            <tr><th>Désignation</th><th>Qté</th><th>Prix HT</th><th>Remise</th><th class="right">Total HT</th></tr>
        </thead>
        <tbody>
        @foreach ($invoice->items as $i)
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
        <tr><td>Total HT</td><td class="right">{{ number_format($invoice->subtotal_ht, 2, ',', ' ') }} DH</td></tr>
        <tr><td>TVA ({{ $invoice->tax_rate + 0 }}%)</td><td class="right">{{ number_format($invoice->tax_amount, 2, ',', ' ') }} DH</td></tr>
        <tr><td><strong>Total TTC</strong></td><td class="right"><strong>{{ number_format($invoice->total_ttc, 2, ',', ' ') }} DH</strong></td></tr>
        @if ($invoice->type === 'facture' && ! $invoice->isDraft())
            <tr><td>Déjà payé</td><td class="right">{{ number_format($invoice->amount_paid, 2, ',', ' ') }} DH</td></tr>
            <tr><td><strong>Reste à payer</strong></td><td class="right"><strong>{{ number_format($invoice->balance(), 2, ',', ' ') }} DH</strong></td></tr>
        @endif
    </table>

    @if ($invoice->terms)
        <p><strong>Conditions :</strong><br>{!! nl2br(e($invoice->terms)) !!}</p>
    @endif

    <div class="footer">{{ config('commerce.invoice_footer') }}</div>
</body>
</html>