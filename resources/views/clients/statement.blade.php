<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8">@include('pdf._style')</head>
<body>
    @include('pdf._header', ['title' => 'RELEVÉ DE COMPTE', 'ref' => $client->name, 'date' => 'Au ' . now()->format('d/m/Y')])

    <p style="margin-top: 16px;">
        <strong>{{ $client->name }}</strong>@if ($client->company) — {{ $client->company }} @endif<br>
        {{ $client->address }} {{ $client->city }}
        @if ($client->ice)<br>ICE : {{ $client->ice }}@endif
    </p>

    <table class="grid">
        <thead><tr><th>Date</th><th>Opération</th><th class="right">Débit</th><th class="right">Crédit</th><th class="right">Solde</th></tr></thead>
        <tbody>
        @forelse ($rows as $r)
            <tr>
                <td>{{ $r['date']->format('d/m/Y') }}</td>
                <td>{{ $r['label'] }}</td>
                <td class="right">{{ $r['debit'] ? number_format($r['debit'], 2, ',', ' ') : '' }}</td>
                <td class="right">{{ $r['credit'] ? number_format($r['credit'], 2, ',', ' ') : '' }}</td>
                <td class="right">{{ number_format($r['balance'], 2, ',', ' ') }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center">Aucune opération.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="box">
        <div class="muted">SOLDE À PAYER</div>
        <div class="big">{{ number_format($balance, 2, ',', ' ') }} DH</div>
    </div>

    <div class="footer">{{ config('commerce.invoice_footer') }}</div>
</body></html>
