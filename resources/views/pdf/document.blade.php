{{-- HN BLOOM | PDF universel. Appeler avec ['doc' => $doc, 'template' => 'signature'] --}}
@php
    $doc = $doc ?? [];
    $template = $template ?? config('commerce.pdf_template', 'signature');
    $template = in_array($template, ['signature', 'prestige', 'editorial'], true) ? $template : 'signature';

    $company = array_merge([
        'name' => 'HN BLOOM',
        'address' => '120, bd La grand ceinture IMM Liliya II, ETG 3 N°27, 20570 Casablanca',
        'phone' => '0522351333',
        'email' => 'contact@hnbloom.ma',
        'website' => 'www.hnbloom.ma',
        'rc' => '597891', 'patente' => '35554409', 'if' => '53992490',
        'cnss' => '5918706', 'ice' => '003367936000027',
    ], config('commerce.company', []));

    $type = $doc['type'] ?? 'devis';
    $titles = [
        'devis' => 'DEVIS', 'facture' => 'FACTURE',
        'recu' => 'REÇU DE PAIEMENT', 'commande' => 'BON DE COMMANDE',
        'avoir' => 'AVOIR', 'bon_livraison' => 'BON DE LIVRAISON',
    ];
    $title = $doc['title'] ?? ($titles[$type] ?? 'DOCUMENT COMMERCIAL');
    $number = $doc['number'] ?? 'À COMPLÉTER';
    $date = $doc['date'] ?? now()->format('d/m/Y');
    $dueDate = $doc['due_date'] ?? ($doc['valid_until'] ?? null);
    $client = array_merge(['name'=>'Client','address'=>'Adresse client','phone'=>'','email'=>'','ice'=>''], $doc['client'] ?? []);
    $items = $doc['items'] ?? [];
    $currency = $doc['currency'] ?? 'DH';
    $subtotal = (float)($doc['subtotal'] ?? collect($items)->sum(fn($i) => (float)($i['total_ht'] ?? 0)));
    $discount = (float)($doc['discount'] ?? 0);
    $tax = (float)($doc['tax'] ?? ($subtotal * 0.20));
    $total = (float)($doc['total'] ?? ($subtotal - $discount + $tax));
    $paid = (float)($doc['paid'] ?? 0);
    $balance = (float)($doc['balance'] ?? max(0, $total - $paid));
    $notes = $doc['notes'] ?? 'Nous vous remercions pour votre confiance.';
    $terms = $doc['terms'] ?? null;
    $paymentMethod = $doc['payment_method'] ?? null;
    $logoPath = public_path('images/hn-bloom-logo.png');
    $qrPath = public_path('images/hn-bloom-website-qr.png');
    $money = fn($n) => number_format((float)$n, 2, ',', ' ') . ' ' . $currency;
@endphp
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
@include('pdf.templates._styles', ['template' => $template])
</head>
<body class="theme-{{ $template }}">
<div class="page-shell">
    <table class="topline"><tr><td></td></tr></table>
    <table class="masthead">
        <tr>
            <td class="brand-cell">
                @if(file_exists($logoPath)) <img class="brand-logo" src="{{ $logoPath }}" alt="HN BLOOM"> @else
                    <div class="brand-fallback"><span>HN</span><small>HN BLOOM</small></div>
                @endif
            </td>
            <td class="brand-center">
                <div class="brand-motto">INNOVATION&nbsp; • &nbsp;QUALITÉ&nbsp; • &nbsp;CONFIANCE</div>
                <div class="brand-sub">{{ $company['website'] }}</div>
            </td>
            <td class="brand-contact">
                <div>{{ $company['phone'] }}</div>
                <div>{{ $company['email'] }}</div>
            </td>
        </tr>
    </table>

    <table class="title-row">
        <tr>
            <td>
                <div class="document-title">{{ $title }}</div>
                <div class="document-number">N° {{ $number }}</div>
            </td>
            <td class="date-cell">
                <div><span class="label">DATE D'ÉMISSION</span><strong>{{ $date }}</strong></div>
                @if($dueDate)
                    <div class="due"><span class="label">{{ $type === 'devis' ? 'VALABLE JUSQU’AU' : 'ÉCHÉANCE' }}</span><strong>{{ $dueDate }}</strong></div>
                @endif
                @if(!empty($doc['status']))
                    <span class="status">{{ strtoupper($doc['status']) }}</span>
                @endif
            </td>
        </tr>
    </table>

    <table class="client-row">
        <tr>
            <td class="client-card">
                <div class="label">FACTURÉ À / CLIENT</div>
                <strong>{{ $client['name'] }}</strong>
                @if($client['address'])<div>{{ $client['address'] }}</div>@endif
                @if($client['phone'])<div>Tél. {{ $client['phone'] }}</div>@endif
                @if($client['email'])<div>{{ $client['email'] }}</div>@endif
                @if($client['ice'])<div>ICE : {{ $client['ice'] }}</div>@endif
            </td>
            <td class="meta-card">
                <div class="label">INFORMATIONS DU DOCUMENT</div>
                <div>Référence : <strong>{{ $number }}</strong></div>
                <div>Date : <strong>{{ $date }}</strong></div>
                @if($dueDate)<div>Échéance : <strong>{{ $dueDate }}</strong></div>@endif
                @if($paymentMethod)<div>Règlement : <strong>{{ $paymentMethod }}</strong></div>@endif
            </td>
        </tr>
    </table>

    <div class="section-heading">DÉTAIL DES ARTICLES ET PRESTATIONS</div>
    <table class="items">
        <thead><tr>
            <th class="designation">DÉSIGNATION</th><th>QTÉ</th><th>PRIX HT</th><th>TVA</th><th>REMISE</th><th class="last">TOTAL HT</th>
        </tr></thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td class="designation">
                    <strong>{{ $item['name'] ?? $item['designation'] ?? 'Article' }}</strong>
                    @if(!empty($item['reference']))<small>Réf. {{ $item['reference'] }}</small>@endif
                    @if(!empty($item['description']))<small>{{ $item['description'] }}</small>@endif
                </td>
                <td class="num">{{ $item['quantity'] ?? 1 }} {{ $item['unit'] ?? 'u' }}</td>
                <td class="num">{{ $money($item['unit_price'] ?? 0) }}</td>
                <td class="num">{{ $item['tax_rate'] ?? 20 }} %</td>
                <td class="num">{{ $item['discount'] ?? 0 }} %</td>
                <td class="num last">{{ $money($item['total_ht'] ?? (($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0))) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty-row">Aucun article renseigné pour ce document.</td></tr>
        @endforelse
        </tbody>
    </table>

    <table class="lower-row">
        <tr>
            <td class="notes-cell">
                <div class="thankyou">Merci pour votre confiance</div>
                <div class="thankyou-brand">HN BLOOM</div>
                @if($notes)<div class="notes">{{ $notes }}</div>@endif
                @if($terms)<div class="terms"><strong>Conditions :</strong> {{ $terms }}</div>@endif
            </td>
            <td class="totals-cell">
                <table class="totals">
                    <tr><td>Total HT</td><td class="amount">{{ $money($subtotal) }}</td></tr>
                    @if($discount > 0)<tr><td>Remise</td><td class="amount">- {{ $money($discount) }}</td></tr>@endif
                    <tr><td>TVA</td><td class="amount">{{ $money($tax) }}</td></tr>
                    @if($type === 'recu')
                        <tr><td>Montant reçu</td><td class="amount">{{ $money($paid ?: $total) }}</td></tr>
                        <tr><td>Reste à payer</td><td class="amount">{{ $money($balance) }}</td></tr>
                    @endif
                    <tr class="grand-total"><td>TOTAL TTC</td><td class="amount">{{ $money($total) }}</td></tr>
                </table>
                <div class="amount-words">{{ $doc['amount_in_words'] ?? '' }}</div>
            </td>
        </tr>
    </table>

    @if(!empty($doc['payment_reference']))
        <div class="payment-box"><strong>Référence de paiement :</strong> {{ $doc['payment_reference'] }}</div>
    @endif

    <table class="signature-row">
        <tr>
            <td><div class="signature-title">Cachet et signature HN BLOOM</div><div class="signature-line"></div></td>
            <td><div class="signature-title">{{ $type === 'devis' || $type === 'commande' ? 'Bon pour accord' : 'Signature du client' }}</div><div class="signature-line"></div><small>Nom, date et signature</small></td>
        </tr>
    </table>
</div>

<div class="footer">
    <table><tr>
        <td class="qr-cell">
            @if(file_exists($qrPath))<img class="qr" src="{{ $qrPath }}" alt="QR HN BLOOM">@endif
        </td>
        <td class="footer-company">
            <strong>{{ $company['name'] }}</strong><br>
            {{ $company['address'] }}<br>
            RC : {{ $company['rc'] }} &nbsp;•&nbsp; Patente : {{ $company['patente'] }} &nbsp;•&nbsp; IF : {{ $company['if'] }}<br>
            CNSS : {{ $company['cnss'] }} &nbsp;•&nbsp; ICE : {{ $company['ice'] }}
        </td>
        <td class="footer-web"><strong>{{ $company['website'] }}</strong><br>Scannez pour visiter notre site</td>
    </tr></table>
</div>
</body></html>
