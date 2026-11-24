<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th { background: #111; color: #e6c565; text-align: left; padding: 6px; }
        td { padding: 6px; border-bottom: 1px solid #ddd; }
        .r { text-align: right; }
        .sum { margin-top: 16px; width: 40%; margin-left: auto; }
        .sum td { border: 0; padding: 3px 6px; }
        .tot td { border-top: 2px solid #111; font-weight: bold; font-size: 13px; }
    </style>
</head>
<body>
    @php
        $label = ['especes' => 'Espèces', 'cheque' => 'Chèque', 'virement' => 'Virement'];
        $d = fn ($x) => \Illuminate\Support\Carbon::parse($x)->format('d/m/Y');
        $m = fn ($x) => number_format((float) $x, 2, ',', ' ');
    @endphp

    <h1>{{ config('commerce.company.name') }} — Liste des paiements</h1>
    <div class="muted">Période : {{ $d($from) }} au {{ $d($to) }} · {{ $payments->count() }} paiement(s)</div>

    <table>
        <thead>
            <tr>
                <th>Date</th><th>N° paiement</th><th>Facture</th><th>Client</th>
                <th>Mode</th><th>Référence</th><th class="r">Montant (DH)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payments as $p)
                <tr>
                    <td>{{ $d($p->paid_at) }}</td>
                    <td>{{ $p->number }}</td>
                    <td>{{ $invoices[$p->invoice_id] ?? '—' }}</td>
                    <td>{{ $clients[$p->client_id] ?? '—' }}</td>
                    <td>{{ $label[$p->method] ?? $p->method }}</td>
                    <td>{{ $p->method === 'cheque' ? ($p->cheque_number ?: '—') : ($p->reference ?: '—') }}</td>
                    <td class="r">{{ $m($p->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">Aucun paiement sur cette période.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="sum">
        @foreach ($byMethod as $k => $v)
            <tr><td>{{ $label[$k] ?? $k }}</td><td class="r">{{ $m($v) }} DH</td></tr>
        @endforeach
        <tr class="tot"><td>Total encaissé</td><td class="r">{{ $m($total) }} DH</td></tr>
    </table>
</body>
</html>
