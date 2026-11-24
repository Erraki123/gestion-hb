<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8">@include('pdf._style')</head>
<body>
    @php
        $isFacture = $invoice->type === 'facture';
        $cancelled = $invoice->status === 'annulee';
        $paid      = $invoice->status === 'payee';
        $late      = $invoice->isOverdue();
        $payments  = $isFacture && ! $invoice->isDraft() ? $invoice->payments : collect();

        // « Déjà payé » et « Reste à payer » : seulement s'il y a eu un paiement (ou un avoir).
        // Une facture pas encore encaissée affiche juste son total.
        $hasMovement = $isFacture && ! $invoice->isDraft() && ! $cancelled
            && ((float) $invoice->amount_paid > 0 || (float) $invoice->creditedAmount() > 0);
        $balance   = $hasMovement ? $invoice->balance() : null;

        $words     = \App\Support\Money::words((float) $invoice->total_ttc);
        $breakdown = \App\Services\DocumentTotals::breakdown($invoice->items);
    @endphp

    @include('pdf._header', [
        'title' => strtoupper($invoice->title()),
        'ref'   => $invoice->displayNumber(),
        'date'  => 'Date : ' . $invoice->issue_date->format('d/m/Y') . ($invoice->due_date ? '  ·  Échéance : ' . $invoice->due_date->format('d/m/Y') : ''),
    ])

    <div class="page">
        <table class="hn-info-grid" style="margin-top: 12px;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 8px;">
                    <div class="card">
                        <div class="card-label">@if(file_exists(public_path('images/hnb-pdf-icons/building.png')))<img class="hn-card-icon" src="{{ public_path('images/hnb-pdf-icons/building.png') }}" alt="">@endif Émetteur</div>
                        <strong>{{ config('commerce.company.name') }}</strong><br>
                        {{ config('commerce.company.address') }}<br>
                        @if (config('commerce.company.ice')) ICE : {{ config('commerce.company.ice') }} @endif<br>@if (config('commerce.company.phone')) {{ config('commerce.company.phone') }}<br>@endif @if (config('commerce.company.email')) {{ config('commerce.company.email') }} @endif
                    </div>
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 8px;">
                    <div class="card">
                        <div class="card-label">@if(file_exists(public_path('images/hnb-pdf-icons/user.png')))<img class="hn-card-icon" src="{{ public_path('images/hnb-pdf-icons/user.png') }}" alt="">@endif Client</div>
                        <strong>{{ $invoice->client->name }}</strong><br>
                        @if ($invoice->client->company) {{ $invoice->client->company }}<br> @endif
                        {{ $invoice->client->address }} {{ $invoice->client->city }}
                        @if ($invoice->client->ice) <br>ICE : {{ $invoice->client->ice }} @endif @if($invoice->client->phone)<br>{{ $invoice->client->phone }}@endif @if($invoice->client->email)<br>{{ $invoice->client->email }}@endif
                    </div>
                </td>

            </tr>
        </table>

        <div style="margin-top: 12px;">
            @if ($invoice->isDraft()) <span class="stamp stamp-draft">BROUILLON</span> @endif
            @if ($paid) <span class="stamp stamp-paid">PAYÉE</span> @endif
            @if ($late) <span class="stamp stamp-late">EN RETARD</span> @endif
            @if ($cancelled) <span class="stamp stamp-late">ANNULÉE</span> @endif
            @if ($invoice->parent)
                @php $linkedReturn = \App\Models\CustomerReturn::where('credit_note_id', $invoice->id)->first(); @endphp
                <div style="margin-top: 6px;">
                    @if ($invoice->parent->status === 'annulee')
                        Annule la facture {{ $invoice->parent->number }}
                    @else
                        Avoir partiel sur facture N° {{ $invoice->parent->number }} (retour {{ $linkedReturn?->number ?? 'RT-...' }})
                    @endif
                </div>
            @endif
        </div>

        <table class="grid">
            <thead>
                <tr>
                    <th>Désignation</th><th class="right">Qté</th><th class="right">Prix HT</th>
                    <th class="right">TVA</th><th class="right">Remise</th><th class="right">Total HT</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($invoice->items as $i)
                <tr class="{{ $loop->even ? 'alt' : '' }}">
                    <td>{{ $i->name }}@if ($i->sku)<br><span class="muted">{{ $i->sku }}</span>@endif</td>
                    <td class="right">{{ $i->quantity + 0 }} {{ $i->unit }}</td>
                    <td class="right">{{ \App\Support\Money::price($i->unit_price) }}</td>
                    <td class="right">{{ $i->tax_rate + 0 }} %</td>
                    <td class="right">{{ $i->discount_percent + 0 }} %</td>
                    <td class="right">{{ number_format($i->line_total_ht, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <table class="totals" style="width: 46%; margin-left: 54%; margin-top: 14px;">
            <tr><td>Total HT</td><td class="right">{{ number_format($invoice->subtotal_ht, 2, ',', ' ') }} DH</td></tr>
            @foreach ($breakdown as $b)
                <tr>
                    <td>TVA {{ $b['rate'] + 0 }} % <span class="muted">(base {{ number_format($b['base'], 2, ',', ' ') }})</span></td>
                    <td class="right">{{ number_format($b['tax'], 2, ',', ' ') }} DH</td>
                </tr>
            @endforeach
            @if ((float) $invoice->transport_amount > 0)
                <tr><td>Transport <span class="muted">(sans TVA)</span></td><td class="right">{{ number_format($invoice->transport_amount, 2, ',', ' ') }} DH</td></tr>
            @endif
            @if ((float) $invoice->installation_amount > 0)
                <tr><td>Installation <span class="muted">(sans TVA)</span></td><td class="right">{{ number_format($invoice->installation_amount, 2, ',', ' ') }} DH</td></tr>
            @endif
            <tr class="ttc"><td>TOTAL TTC</td><td class="right">{{ number_format($invoice->total_ttc, 2, ',', ' ') }} DH</td></tr>
            @if ($balance !== null)
                <tr><td>Déjà payé</td><td class="right">{{ number_format($invoice->amount_paid, 2, ',', ' ') }} DH</td></tr>
                <tr><td><strong>Reste à payer</strong></td><td class="right"><strong>{{ number_format($balance, 2, ',', ' ') }} DH</strong></td></tr>
            @endif
        </table>

        @if ($words)
            <div class="hn-words">Arrêtée {{ $isFacture ? 'la présente facture' : 'le présent avoir' }} à la somme de : <strong>{{ $words }}</strong>.</div>
        @endif

        @if ($payments->count())
            <div class="hn-section-heading">Paiements enregistrés</div>
            <table class="grid hn-payments" style="margin-top: 4px;">
                <thead>
                    <tr><th>Date</th><th>N°</th><th>Mode</th><th class="right">Montant</th></tr>
                </thead>
                <tbody>
                @foreach ($payments as $p)
                    <tr class="{{ $loop->even ? 'alt' : '' }}" @if ($p->status === 'annule') style="color:#78746A; text-decoration: line-through;" @endif>
                        <td>{{ $p->paid_at->format('d/m/Y') }}</td>
                        <td>{{ $p->number }}@if ($p->status === 'annule') (annulé)@endif</td>
                        <td>{{ $p->methodLabel() }}</td>
                        <td class="right">{{ number_format($p->amount, 2, ',', ' ') }} DH</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif

        @if ($invoice->terms)
            <div class="hn-conditions"><div class="hn-conditions-title">Conditions</div>{!! nl2br(e($invoice->terms)) !!}</div>
        @endif
        {{-- V18: purely visual signature for short invoices, not a new business field. --}}
        @if ($invoice->items->count() <= 3)
            <div class="hn-visual-closure">
                <div class="hn-closure-label">HN BLOOM <span>INNOVATION &nbsp;·&nbsp; CONFIANCE &nbsp;·&nbsp; EXCELLENCE</span></div>
                <img src="{{ public_path('images/hnb-pdf-v18/signature-lines.png') }}" alt="">
            </div>
        @endif
    </div>

    @include('pdf._footer')
</body>
</html>