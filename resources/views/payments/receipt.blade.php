<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8">@include('pdf._style')</head>
<body>
    @include('pdf._header', ['title' => 'REÇU DE PAIEMENT', 'ref' => $payment->number, 'date' => $payment->paid_at->format('d/m/Y')])

    <table class="info" style="margin-top: 20px;">
        <tr><td style="width:30%" class="muted">Reçu de</td><td><strong>{{ $payment->client->name }}</strong>@if ($payment->client->company) — {{ $payment->client->company }} @endif</td></tr>
        <tr><td class="muted">Facture</td><td>{{ $payment->invoice->number }}</td></tr>
        <tr><td class="muted">Mode de paiement</td>
            <td>{{ $payment->methodLabel() }}
                @if ($payment->method === 'cheque')
                    — chèque n° {{ $payment->cheque_number }} {{ $payment->cheque_bank ? '(' . $payment->cheque_bank . ')' : '' }}
                    {{ $payment->cheque_due_date ? ', échéance ' . $payment->cheque_due_date->format('d/m/Y') : '' }}
                @endif
            </td></tr>
        @if ($payment->reference)<tr><td class="muted">Référence</td><td>{{ $payment->reference }}</td></tr>@endif
        <tr><td class="muted">Encaissé par</td><td>{{ $payment->user?->name }}</td></tr>
    </table>

    <div class="box">
        <div class="muted">MONTANT REÇU</div>
        <div class="big">{{ number_format($payment->amount, 2, ',', ' ') }} DH</div>
    </div>

    @if ($words) <p>Arrêté le présent reçu à la somme de : <strong>{{ $words }}</strong>.</p> @endif

    <p class="muted">Reste à payer sur la facture après ce paiement : {{ number_format($payment->invoice->balance(), 2, ',', ' ') }} DH</p>

    @if ($payment->status === 'annule')
        <p class="void">PAIEMENT ANNULÉ le {{ $payment->cancelled_at?->format('d/m/Y') }} — {{ $payment->cancel_reason }}</p>
    @endif

    <table class="sign"><tr><td>Le client</td><td>Le commercial</td></tr></table>
    <div class="footer">{{ config('commerce.invoice_footer') }}</div>
</body></html>