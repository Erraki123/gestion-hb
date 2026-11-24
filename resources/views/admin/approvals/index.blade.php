<x-app-layout dark-header>
    @php
        $quoteCount = (int) ($counts['quotes'] ?? 0);
        $orderCount = (int) ($counts['orders'] ?? 0);
        $issueCount = $pendingIssueInvoices->count();
        $releaseCount = $pendingInvoices->count();
        $allCount = $quoteCount + $orderCount + $issueCount + $releaseCount;
    @endphp

    <x-slot name="header">
        <div class="relative flex items-center justify-center text-center hnb-ap-page-title">
            <div>
                <h2 class="justify-center !text-lg !leading-tight">Validations</h2>
                <p class="text-xs text-gray-400">{{ $allCount }} demande(s) à traiter</p>
            </div>
            <span class="hnb-ap-header-pill">Direction · Approbations</span>
        </div>
    </x-slot>

    <style>
        .page-head,.page-head > div {padding-top:.5rem !important;padding-bottom:.5rem !important}
        .hnb-ap {--ap-ink:#151614;--ap-gold:#c69b42;--ap-line:#e8e3d9;--ap-muted:#6e7681;max-width:1350px;margin:0 auto;padding:12px 20px 30px;color:#181818}
        .hnb-ap * {box-sizing:border-box}
        .hnb-ap [x-cloak] {display:none !important}
        .hnb-ap-header-pill {position:absolute;right:0;top:50%;transform:translateY(-50%);padding:6px 12px;border:1px solid #caa24f;border-radius:8px;background:#27231d;color:#f6d789;font-size:10px;font-weight:700;letter-spacing:.02em}
        .hnb-ap .panel {border:1px solid #e3ddcf;border-radius:13px;overflow:hidden;background:white;box-shadow:0 3px 15px rgba(0,0,0,.035)}
        .hnb-ap-toolbar {display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:10px 14px;background:#fff;border-bottom:1px solid var(--ap-line)}
        .hnb-ap-toolbar-title {font-size:13px;font-weight:800;color:#1a1b1c}
        .hnb-ap-toolbar-sub {font-size:11px;color:var(--ap-muted);margin-top:2px}
        .hnb-ap-chips {display:flex;align-items:center;justify-content:flex-end;flex-wrap:wrap;gap:7px}
        .hnb-ap-chip {display:inline-flex;align-items:center;gap:7px;border:1px solid #ebe3d2;border-radius:7px;padding:5px 9px;background:#faf8f3;font-size:11px;color:#626263;font-weight:600;white-space:nowrap}
        .hnb-ap-chip b {background:#efe1bf;color:#755216;min-width:18px;text-align:center;border-radius:5px;padding:1px 4px;font-size:11px}
        .hnb-ap-scroll {overflow-x:auto;width:100%}
        .hnb-ap-table {width:100%;min-width:870px;border-collapse:separate;border-spacing:0;font-size:12px}
        .hnb-ap-table > thead > tr.hnb-ap-heading {background:#181914}
        .hnb-ap-table > thead > tr.hnb-ap-heading > th {padding:11px 13px;color:#fff;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.045em;border-bottom:2px solid #c69b42;text-align:left;white-space:nowrap}
        .hnb-ap-table > thead > tr.hnb-ap-heading > th:last-child {text-align:right}
        .hnb-ap-table > thead > tr.hnb-ap-filters {background:#fbfaf7}
        .hnb-ap-table > thead > tr.hnb-ap-filters > th {padding:8px 10px;border-bottom:1px solid #e7dfce;color:#5c6270;font-size:11px;letter-spacing:0;text-transform:none;font-weight:500;background:#fbfaf7}
        .hnb-ap-table input[type=search],.hnb-ap-table select {border:1px solid #d9cbb4;background:#fff;color:#272727;border-radius:9px;font-size:12px;min-height:34px;padding:6px 10px;width:100%;box-shadow:none}
        .hnb-ap-table input[type=search]:focus,.hnb-ap-table select:focus {border-color:#c49b43;outline:2px solid #f2e4c8;outline-offset:0}
        .hnb-ap-filter-note {color:#8b8a83;text-align:right;font-size:10px;font-weight:500}
        .hnb-ap-table > tbody > tr.hnb-ap-row > td {padding:11px 13px;border-bottom:1px solid #f0ece4;background:#fff;vertical-align:middle;line-height:1.35}
        .hnb-ap-table > tbody:hover > tr.hnb-ap-row > td {background:#fffdf8}
        .hnb-ap-table > tbody > tr.hnb-ap-more > td {padding:0;border-bottom:1px solid #e4d5b6;background:#fffdf8}
        .hnb-ap-ref {font-weight:800;color:#171717;font-size:12px;white-space:nowrap}
        .hnb-ap-caption {color:#81818a;font-size:10px;margin-top:3px;line-height:1.35}
        .hnb-ap-client {display:flex;align-items:center;gap:9px}
        .hnb-ap-avatar {width:29px;height:29px;border-radius:50%;background:#121212;color:#e5b957;display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;flex-shrink:0}
        .hnb-ap-client-name {font-weight:700;color:#151515;line-height:1.3;overflow-wrap:anywhere}
        .hnb-ap-type {display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border-radius:6px;border:1px solid #e7d3a8;background:#fff5df;color:#815916;font-size:10px;font-weight:800;white-space:nowrap}
        .hnb-ap-type.order {background:#edf3fa;border-color:#dce7f4;color:#4b617a}
        .hnb-ap-type.invoice {background:#eaf5ef;border-color:#d5eadc;color:#2e7150}
        .hnb-ap-type.release {background:#f1edfa;border-color:#e8def7;color:#6b4b89}
        .hnb-ap-money {font-size:12px;font-weight:800;white-space:nowrap;color:#101110;font-variant-numeric:tabular-nums}
        .hnb-ap-owner {font-size:11px;color:#4f5660;white-space:nowrap}
        .hnb-ap-action {display:inline-flex;justify-content:center;align-items:center;gap:5px;padding:6px 11px;border-radius:7px;border:1px solid #d4a84c;background:#fffaf0;color:#664813;font-weight:800;font-size:11px;white-space:nowrap;cursor:pointer}
        .hnb-ap-action:hover {background:#f9edcd}
        .hnb-ap-action:focus-visible,.hnb-ap a:focus-visible,.hnb-ap button:focus-visible {outline:3px solid #cfac5d;outline-offset:2px}
        .hnb-ap-action svg {width:11px;height:11px}
        .hnb-ap-detail {padding:15px 16px 17px 16px;box-shadow:inset 0 2px 5px rgba(30,25,10,.025)}
        .hnb-ap-detail-head {display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:10px}
        .hnb-ap-detail-head strong {font-size:12px}
        .hnb-ap-links {display:flex;gap:12px;flex-wrap:wrap}
        .hnb-ap-links a {color:#9b6e25;font-size:11px;font-weight:800;text-decoration:underline;text-underline-offset:3px}
        .hnb-ap-mini {border:1px solid #e8e3db;border-radius:9px;overflow-x:auto;background:#fff}
        .hnb-ap-mini table {width:100%;min-width:430px;border-collapse:collapse;font-size:11px}
        .hnb-ap-mini thead {background:#f5f2ed}
        .hnb-ap-mini th {font-size:10px;text-align:left;color:#64615b !important;background:#f5f2ed;border-bottom:1px solid #e8e3db;letter-spacing:.02em;padding:8px 10px}
        .hnb-ap-mini td {border-bottom:1px solid #f0ede8;padding:8px 10px;color:#3a3d42}
        .hnb-ap-mini tbody tr:last-child td {border-bottom:0}
        .hnb-ap-summary {margin:8px 0 3px;text-align:right;font-size:11px;color:#676d73}
        .hnb-ap-summary b {font-weight:800;color:#202020}
        .hnb-ap-note {margin:8px 0 0;font-size:11px;color:#72767e;white-space:pre-wrap;overflow-wrap:anywhere}
        .hnb-ap-decisions {display:flex;align-items:flex-start;gap:9px;flex-wrap:wrap;margin-top:12px;padding-top:12px;border-top:1px dashed #dfd5c4}
        .hnb-ap-decisions form {display:flex;align-items:center;gap:7px;flex-wrap:wrap}
        .hnb-ap-decisions textarea {min-height:33px;min-width:210px;max-width:370px;width:min(33vw,340px);border:1px solid #d9cbb4;border-radius:8px;background:#fff;font-size:11px;padding:7px 9px;resize:vertical;color:#222}
        .hnb-ap-btn {display:inline-flex;align-items:center;justify-content:center;padding:8px 12px;border-radius:8px;font-size:11px;font-weight:800;border:1px solid transparent;white-space:nowrap;cursor:pointer}
        .hnb-ap-btn.approve {background:#181818;color:#efca7a}.hnb-ap-btn.approve:hover {background:#30302c}
        .hnb-ap-btn.refuse {background:#fff;border-color:#e7c9c4;color:#b04b3c}.hnb-ap-btn.refuse:hover {background:#fff0ed}
        .hnb-ap-empty {padding:30px 15px !important;text-align:center;background:#fff !important}
        .hnb-ap-empty strong {display:block;font-size:13px;font-weight:800;color:#252525}.hnb-ap-empty span {display:block;font-size:11px;margin-top:5px;color:#787d85}
        .hnb-ap-notice {margin-bottom:9px;padding:8px 12px;border-radius:9px;font-size:12px;border:1px solid #d4e8d9;background:#f0faf1;color:#235f39}
        .hnb-ap-notice.error {background:#fff0ef;color:#8b342d;border-color:#edceca}
        .hnb-ap-pagers {padding:10px 14px;border-top:1px solid #eee8de}
        .hnb-ap-pagers > div + div {margin-top:8px}
        @media(max-width:760px) {.hnb-ap{padding:10px 10px 23px}.hnb-ap-header-pill{display:none}.hnb-ap-toolbar{padding:10px}.hnb-ap-chips{gap:4px}.hnb-ap-chip{padding:4px 6px;font-size:10px}.hnb-ap-detail{padding:11px}.hnb-ap-decisions form{width:100%}.hnb-ap-decisions textarea{flex:1;max-width:none;width:auto;min-width:120px}}
    </style>

    <div class="hnb-ap" x-data="{ search: '', selectedType: 'all' }">
        @if(session('success'))
            <div class="hnb-ap-notice" role="status">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="hnb-ap-notice error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
        @endif
        <div class="panel">
            <div class="hnb-ap-toolbar">
                <div>
                    <div class="hnb-ap-toolbar-title">Demandes en attente</div>
                    <div class="hnb-ap-toolbar-sub">Consulter les détails avant de prendre une décision</div>
                </div>
                <div class="hnb-ap-chips" aria-label="Répartition des validations">
                    <span class="hnb-ap-chip">Devis <b>{{ $quoteCount }}</b></span>
                    <span class="hnb-ap-chip">Commandes <b>{{ $orderCount }}</b></span>
                    <span class="hnb-ap-chip">Émission <b>{{ $issueCount }}</b></span>
                    <span class="hnb-ap-chip">Envoi <b>{{ $releaseCount }}</b></span>
                </div>
            </div>
            <div class="hnb-ap-scroll">
                <table class="hnb-ap-table">
                    <thead>
                        <tr class="hnb-ap-heading">
                            <th scope="col">Type</th>
                            <th scope="col">Référence</th>
                            <th scope="col">Client</th>
                            <th scope="col">Date</th>
                            <th scope="col">Total TTC</th>
                            <th scope="col">Commercial</th>
                            <th scope="col">Actions</th>
                        </tr>
                        <tr class="hnb-ap-filters">
                            <th>
                                <select x-model="selectedType" aria-label="Filtrer par type de validation">
                                    <option value="all">Tous</option>
                                    <option value="devis">Devis</option>
                                    <option value="commande">Commandes</option>
                                    <option value="emission">Factures à émettre</option>
                                    <option value="envoi">Envois à autoriser</option>
                                </select>
                            </th>
                            <th colspan="3"><input type="search" x-model.debounce.150ms="search" aria-label="Rechercher une validation" placeholder="Référence, client, commercial…" autocomplete="off"></th>
                            <th colspan="3" class="hnb-ap-filter-note">{{ $allCount }} en attente · Filtres sur cette page</th>
                        </tr>
                    </thead>
                    @if($allCount === 0)
                        <tbody><tr><td colspan="7" class="hnb-ap-empty"><strong>Aucune validation en attente</strong><span>Les nouvelles demandes apparaîtront ici automatiquement.</span></td></tr></tbody>
                    @endif

                    @foreach($quotes as $quote)
                        <tbody x-data="{ open: false }" data-search="{{ \Illuminate\Support\Str::lower(($quote->number ?? '').' '.($quote->client?->name ?? '').' '.($quote->commercial?->name ?? '')) }}" x-show="(selectedType === 'all' || selectedType === 'devis') && (!search.trim() || $el.dataset.search.includes(search.trim().toLowerCase()))">
                            <tr class="hnb-ap-row">
                                <td><span class="hnb-ap-type">Devis</span></td>
                                <td><div class="hnb-ap-ref">{{ $quote->number }}</div><div class="hnb-ap-caption">À approuver</div></td>
                                <td><div class="hnb-ap-client"><span class="hnb-ap-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($quote->client?->name ?? 'CL', 0, 2)) }}</span><div class="hnb-ap-client-name">{{ $quote->client?->name ?? 'Client indisponible' }}</div></div></td>
                                <td>{{ $quote->approval_requested_at?->format('d/m/Y') ?? $quote->issue_date?->format('d/m/Y') ?? '—' }}</td>
                                <td><span class="hnb-ap-money">{{ number_format((float)$quote->total_ttc, 2, ',', ' ') }} DH</span></td>
                                <td><span class="hnb-ap-owner">{{ $quote->commercial?->name ?? '—' }}</span></td>
                                <td style="text-align:right"><button type="button" class="hnb-ap-action" @click="open = !open" :aria-expanded="open.toString()" aria-label="Examiner le devis {{ $quote->number }}"><span x-text="open ? 'Fermer' : 'Examiner'">Examiner</span> <span aria-hidden="true" x-text="open ? '−' : '+'">+</span></button></td>
                            </tr>
                            <tr class="hnb-ap-more" x-show="open" x-cloak><td colspan="7"><div class="hnb-ap-detail">
                                <div class="hnb-ap-detail-head"><strong>Détails du devis {{ $quote->number }}</strong><div class="hnb-ap-links"><a href="{{ route('quotes.show',$quote) }}">Voir le devis</a><a href="{{ route('quotes.pdf',$quote) }}" target="_blank" rel="noopener">Aperçu PDF</a></div></div>
                                <div class="hnb-ap-mini"><table><thead><tr><th>Produit / Service</th><th>Qté</th><th>Prix HT</th><th>Remise</th><th>TVA</th></tr></thead><tbody>
                                    @forelse($quote->items as $item)<tr><td>{{ $item->name }}</td><td>{{ $item->quantity }}</td><td>{{ number_format((float)$item->unit_price, 2, ',', ' ') }} DH</td><td>{{ $item->discount_percent }}%</td><td>{{ $item->tax_rate }}%</td></tr>@empty<tr><td colspan="5">Aucune ligne</td></tr>@endforelse
                                </tbody></table></div>
                                <p class="hnb-ap-summary">HT {{ number_format((float)$quote->subtotal_ht,2,',',' ') }} DH · TVA {{ number_format((float)$quote->tax_amount,2,',',' ') }} DH · <b>TTC {{ number_format((float)$quote->total_ttc,2,',',' ') }} DH</b></p>
                                @if($quote->terms || $quote->notes)<p class="hnb-ap-note">{{ $quote->terms }} {{ $quote->notes }}</p>@endif
                                <div class="hnb-ap-decisions">
                                    <form method="POST" action="{{ route('quotes.approval.decide',$quote) }}">@csrf<input type="hidden" name="decision" value="approved"><button type="submit" class="hnb-ap-btn approve">Approuver le devis</button></form>
                                    <form method="POST" action="{{ route('quotes.approval.decide',$quote) }}">@csrf<input type="hidden" name="decision" value="rejected"><textarea rows="1" name="comment" required maxlength="2000" aria-label="Motif du refus du devis" placeholder="Motif du refus obligatoire"></textarea><button type="submit" class="hnb-ap-btn refuse">Refuser</button></form>
                                </div>
                            </div></td></tr>
                        </tbody>
                    @endforeach

                    @foreach($orders as $order)
                        <tbody x-data="{ open: false }" data-search="{{ \Illuminate\Support\Str::lower(($order->number ?? '').' '.($order->client?->name ?? '').' '.($order->commercial?->name ?? '')) }}" x-show="(selectedType === 'all' || selectedType === 'commande') && (!search.trim() || $el.dataset.search.includes(search.trim().toLowerCase()))">
                            <tr class="hnb-ap-row">
                                <td><span class="hnb-ap-type order">Commande</span></td>
                                <td><div class="hnb-ap-ref">{{ $order->number }}</div><div class="hnb-ap-caption">À confirmer</div></td>
                                <td><div class="hnb-ap-client"><span class="hnb-ap-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($order->client?->name ?? 'CL',0,2)) }}</span><div class="hnb-ap-client-name">{{ $order->client?->name ?? 'Client indisponible' }}</div></div></td>
                                <td>{{ $order->approval_requested_at?->format('d/m/Y') ?? '—' }}</td>
                                <td><span class="hnb-ap-money">{{ number_format((float)$order->total_ttc,2,',',' ') }} DH</span></td>
                                <td><span class="hnb-ap-owner">{{ $order->commercial?->name ?? '—' }}</span></td>
                                <td style="text-align:right"><button type="button" class="hnb-ap-action" @click="open = !open" :aria-expanded="open.toString()" aria-label="Examiner la commande {{ $order->number }}"><span x-text="open ? 'Fermer' : 'Examiner'">Examiner</span> <span aria-hidden="true" x-text="open ? '−' : '+'">+</span></button></td>
                            </tr>
                            <tr class="hnb-ap-more" x-show="open" x-cloak><td colspan="7"><div class="hnb-ap-detail">
                                <div class="hnb-ap-detail-head"><strong>Commande {{ $order->number }}</strong><div class="hnb-ap-links"><a href="{{ route('orders.show',$order) }}">Voir la commande</a></div></div>
                                <div class="hnb-ap-decisions">
                                    <form method="POST" action="{{ route('orders.approval.decide',$order) }}">@csrf<input type="hidden" name="decision" value="approved"><button type="submit" class="hnb-ap-btn approve">Confirmer la commande</button></form>
                                    <form method="POST" action="{{ route('orders.approval.decide',$order) }}">@csrf<input type="hidden" name="decision" value="rejected"><textarea rows="1" name="comment" maxlength="2000" aria-label="Motif du refus de la commande" placeholder="Motif du refus"></textarea><button type="submit" class="hnb-ap-btn refuse">Refuser</button></form>
                                </div>
                            </div></td></tr>
                        </tbody>
                    @endforeach

                    @foreach($pendingIssueInvoices as $invoice)
                        <tbody x-data="{ open: false }" data-search="{{ \Illuminate\Support\Str::lower(($invoice->number ?? ('Brouillon #'.$invoice->id)).' '.($invoice->client?->name ?? '').' '.($invoice->commercial?->name ?? '')) }}" x-show="(selectedType === 'all' || selectedType === 'emission') && (!search.trim() || $el.dataset.search.includes(search.trim().toLowerCase()))">
                            <tr class="hnb-ap-row">
                                <td><span class="hnb-ap-type invoice">Émission</span></td>
                                <td><div class="hnb-ap-ref">{{ $invoice->number ?? 'Brouillon #'.$invoice->id }}</div><div class="hnb-ap-caption">Facture à émettre</div></td>
                                <td><div class="hnb-ap-client"><span class="hnb-ap-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($invoice->client?->name ?? 'CL',0,2)) }}</span><div class="hnb-ap-client-name">{{ $invoice->client?->name ?? 'Client indisponible' }}</div></div></td>
                                <td>{{ $invoice->created_at?->format('d/m/Y') ?? '—' }}</td>
                                <td><span class="hnb-ap-money">{{ number_format((float)$invoice->total_ttc,2,',',' ') }} DH</span></td>
                                <td><span class="hnb-ap-owner">{{ $invoice->commercial?->name ?? '—' }}</span></td>
                                <td style="text-align:right"><button type="button" class="hnb-ap-action" @click="open = !open" :aria-expanded="open.toString()" aria-label="Examiner la facture brouillon #{{ $invoice->id }}"><span x-text="open ? 'Fermer' : 'Examiner'">Examiner</span> <span aria-hidden="true" x-text="open ? '−' : '+'">+</span></button></td>
                            </tr>
                            <tr class="hnb-ap-more" x-show="open" x-cloak><td colspan="7"><div class="hnb-ap-detail">
                                <div class="hnb-ap-detail-head"><strong>Facture à émettre · {{ $invoice->order_id ? 'Depuis commande' : 'Facture directe' }}</strong><div class="hnb-ap-links"><a href="{{ route('invoices.show',$invoice) }}">Voir la facture</a><a href="{{ route('invoices.pdf',$invoice) }}" target="_blank" rel="noopener">Aperçu PDF</a></div></div>
                                <div class="hnb-ap-mini"><table><thead><tr><th>Produit</th><th>Qté</th><th>Prix HT</th><th>Remise</th></tr></thead><tbody>@forelse($invoice->items as $item)<tr><td>{{ $item->name }}</td><td>{{ $item->quantity }}</td><td>{{ number_format((float)$item->unit_price,2,',',' ') }} DH</td><td>{{ $item->discount_percent }}%</td></tr>@empty<tr><td colspan="4">Aucune ligne</td></tr>@endforelse</tbody></table></div>
                                <div class="hnb-ap-decisions">
                                    <form method="POST" action="{{ route('invoices.approval.decide',$invoice) }}">@csrf<input type="hidden" name="decision" value="approved"><button type="submit" class="hnb-ap-btn approve">Approuver et émettre</button></form>
                                    <form method="POST" action="{{ route('invoices.approval.decide',$invoice) }}">@csrf<input type="hidden" name="decision" value="rejected"><textarea rows="1" name="comment" required maxlength="2000" aria-label="Motif du refus de la facture" placeholder="Motif du refus obligatoire"></textarea><button type="submit" class="hnb-ap-btn refuse">Refuser</button></form>
                                </div>
                            </div></td></tr>
                        </tbody>
                    @endforeach

                    @foreach($pendingInvoices as $invoice)
                        <tbody x-data="{ open: false }" data-search="{{ \Illuminate\Support\Str::lower(($invoice->number ?? ('Facture #'.$invoice->id)).' '.($invoice->client?->name ?? '')) }}" x-show="(selectedType === 'all' || selectedType === 'envoi') && (!search.trim() || $el.dataset.search.includes(search.trim().toLowerCase()))">
                            <tr class="hnb-ap-row">
                                <td><span class="hnb-ap-type release">Envoi</span></td>
                                <td><div class="hnb-ap-ref">{{ $invoice->number ?? 'Facture #'.$invoice->id }}</div><div class="hnb-ap-caption">Envoi à autoriser</div></td>
                                <td><div class="hnb-ap-client"><span class="hnb-ap-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($invoice->client?->name ?? 'CL',0,2)) }}</span><div class="hnb-ap-client-name">{{ $invoice->client?->name ?? 'Client indisponible' }}</div></div></td>
                                <td>{{ $invoice->created_at?->format('d/m/Y') ?? '—' }}</td>
                                <td><span class="hnb-ap-money">{{ number_format((float)$invoice->total_ttc,2,',',' ') }} DH</span></td>
                                <td><span class="hnb-ap-owner">—</span></td>
                                <td style="text-align:right"><button type="button" class="hnb-ap-action" @click="open = !open" :aria-expanded="open.toString()" aria-label="Examiner l'envoi de facture #{{ $invoice->id }}"><span x-text="open ? 'Fermer' : 'Examiner'">Examiner</span> <span aria-hidden="true" x-text="open ? '−' : '+'">+</span></button></td>
                            </tr>
                            <tr class="hnb-ap-more" x-show="open" x-cloak><td colspan="7"><div class="hnb-ap-detail">
                                <div class="hnb-ap-detail-head"><strong>Autorisation d'envoi de la facture</strong><div class="hnb-ap-links"><a href="{{ route('invoices.show',$invoice) }}">Voir la facture</a><a href="{{ route('invoices.pdf',$invoice) }}" target="_blank" rel="noopener">Aperçu PDF</a></div></div>
                                <div class="hnb-ap-mini"><table><thead><tr><th>Produit</th><th>Qté</th><th>Prix HT</th><th>Remise</th></tr></thead><tbody>@forelse($invoice->items as $item)<tr><td>{{ $item->name }}</td><td>{{ $item->quantity }}</td><td>{{ number_format((float)$item->unit_price,2,',',' ') }} DH</td><td>{{ $item->discount_percent }}%</td></tr>@empty<tr><td colspan="4">Aucune ligne</td></tr>@endforelse</tbody></table></div>
                                <div class="hnb-ap-decisions">
                                    <form method="POST" action="{{ route('documents.release.decide',['type'=>'invoice','id'=>$invoice->id]) }}">@csrf<input type="hidden" name="decision" value="approved"><button type="submit" class="hnb-ap-btn approve">Autoriser l'envoi</button></form>
                                    <form method="POST" action="{{ route('documents.release.decide',['type'=>'invoice','id'=>$invoice->id]) }}">@csrf<input type="hidden" name="decision" value="rejected"><textarea rows="1" name="comment" required maxlength="1000" aria-label="Motif du refus de l'envoi" placeholder="Motif du refus obligatoire"></textarea><button type="submit" class="hnb-ap-btn refuse">Refuser</button></form>
                                </div>
                            </div></td></tr>
                        </tbody>
                    @endforeach
                </table>
            </div>
            @if($quotes->hasPages() || $orders->hasPages())
                <div class="hnb-ap-pagers">
                    @if($quotes->hasPages())<div>{{ $quotes->withQueryString()->links() }}</div>@endif
                    @if($orders->hasPages())<div>{{ $orders->withQueryString()->links() }}</div>@endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
