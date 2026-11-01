<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8">@include('pdf._style')</head>
<body>
    @include('pdf._header', ['title' => 'SORTIE DE COMMISSIONS', 'ref' => $payout->number, 'date' => $payout->paid_at->format('d/m/Y')])

    <table class="info" style="margin-top: 20px;">
        <tr><td style="width:30%" class="muted">Bénéficiaire</td><td><strong>{{ $payout->user?->name }}</strong></td></tr>
        <tr><td class="muted">Mode de paiement</td><td>{{ $payout->methodLabel() }} {{ $payout->reference ? '· Réf. ' . $payout->reference : '' }}</td></tr>
        <tr><td class="muted">Validé par</td><td>{{ $payout->validator?->name }}</td></tr>
    </table>

    <table class="grid" style="margin-top: 14px;">
        <thead><tr><th>Opération</th><th>Règle</th><th class="right">Base</th><th class="right">Commission</th></tr></thead>
        <tbody>
        @foreach ($payout->commissions as $c)
            <tr>
                <td>{{ $c->sourceLabel() }}</td>
                <td>{{ $c->ruleLabel() }}</td>
                <td class="right">{{ number_format($c->base_amount, 2, ',', ' ') }}</td>
                <td class="right">{{ number_format($c->amount, 2, ',', ' ') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="box">
        <div class="muted">TOTAL VERSÉ</div>
        <div class="big">{{ number_format($payout->amount, 2, ',', ' ') }} DH</div>
    </div>

    @if ($payout->notes) <p>{{ $payout->notes }}</p> @endif

    <table class="sign"><tr><td>Le bénéficiaire</td><td>La direction</td></tr></table>
    <div class="footer">{{ config('commerce.company.name') }}</div>
</body></html>
