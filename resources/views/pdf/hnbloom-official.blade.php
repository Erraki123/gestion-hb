{{-- HN BLOOM official document template. Preview integration only; maintain approval/security gates in the existing controllers. --}}
@php
    $doc = $doc ?? [];
    $type = $doc['type'] ?? 'devis';
    $titles = ['devis'=>'DEVIS','facture'=>'FACTURE','commande'=>'BON DE COMMANDE','bon_livraison'=>'BON DE LIVRAISON','recu'=>'REÇU DE PAIEMENT'];
    $title = $doc['title'] ?? ($titles[$type] ?? 'DOCUMENT');
    $num = $doc['number'] ?? 'BROUILLON';
    $date = $doc['date'] ?? now()->format('d/m/Y');
    $client = $doc['client'] ?? [];
    $company = config('commerce.company', []);
    $items = $doc['items'] ?? [];
    $hasPrices = !in_array($type, ['bon_livraison','recu'], true);
    $money = fn ($v) => number_format((float)$v,2,',',' ') . ' ' . ($doc['currency'] ?? 'DH');
    $logo = public_path('images/hnbloom_logo_reference.png');
@endphp
<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><title>{{ $title }} {{ $num }}</title>
<style>
@page { size: A4 portrait; margin: 0; }
* { box-sizing: border-box; }
body { margin:0; font-family: DejaVu Sans, sans-serif; font-size:10px; color:#111; background:#fcfbf8; }
.frame { position:relative; padding:29px 32px 85px; min-height: 297mm; }
.top-angle { position:absolute;top:0;right:0;width:0;height:0;border-left:145px solid transparent;border-top:82px solid #141414;z-index:-1; }
.top-gold { position:absolute;top:0;right:0;width:0;height:0;border-left:170px solid transparent;border-top:94px solid #c9a057;z-index:-2; }
.header { width:100%;border-collapse:collapse;height:103px; }
.header .logo { width:42%;vertical-align:middle;border-right:1px solid #b68b38; }
.header .logo img { width:190px;max-height:106px; }
.header .motto { vertical-align:middle;text-align:center;font-size:12px;letter-spacing:4px;line-height:1.9; }
.header .motto span { border-bottom:1px solid #b68b38;padding-bottom:9px; }
.title-table {width:100%;border-collapse:collapse;margin-top:20px;}
.title {font-size:29px;font-weight:bold;letter-spacing:.4px;border-bottom:1px solid #bf954d;padding-bottom:6px;}
.meta {width:176px;border:1px solid #c99a44;border-radius:8px;padding:12px;font-size:11px;line-height:1.8;}
.meta b {float:right;}
.section {margin-top:17px;padding-left:13px;border-left:2px solid #c5a052;}
.section-title {font-size:15px;font-weight:bold;margin-bottom:9px;}
.details {width:100%;border-collapse:collapse;} .details td {padding:4px 0;} .details td:first-child {width:150px;color:#666;}
.lines {width:100%;border-collapse:collapse;margin-top:19px;table-layout:fixed;}
.lines th {background:#151515;color:#f4e6c7;border-left:1px solid #ad863c;padding:10px 6px;font-size:9px;text-align:left;}
.lines td {padding:10px 6px;border:1px solid #ddd9d1;vertical-align:top;word-wrap:break-word;}
.lines tr:nth-child(even) td {background:#f8f6f1;}
.right {text-align:right;}
.totals {width:45%;margin-left:55%;border-collapse:collapse;margin-top:13px;}
.totals td {padding:9px;border:1px solid #d4b26b;} .totals td:last-child {text-align:right;}
.totals .final td {background:#121212;color:#e6bf70;font-weight:bold;font-size:12px;}
.conditions {margin-top:22px;max-width:50%;line-height:1.7;}
.conditions .heading {font-size:13px;font-weight:bold;border-bottom:1px solid #c69e57;padding-bottom:7px;margin-bottom:6px;}
.status {display:inline-block;margin-top:8px;padding:5px 9px;border:1px solid #c7a466;color:#72511c;font-weight:bold;}
.footer {position:fixed;bottom:0;left:0;right:0;height:56px;background:#111;color:#fff;border-top:4px solid #c5a05b;padding:13px 31px 7px;font-size:8.2px;}
.footer table {width:100%;border-collapse:collapse;} .footer td {width:33%;vertical-align:top;color:#f7f4ed;}
.footer b {color:#eac875;}
@media print { .frame { min-height:auto; } thead {display:table-header-group;} tr {page-break-inside:avoid;} }
</style></head><body>
<div class="top-gold"></div><div class="top-angle"></div>
<div class="frame">
<table class="header"><tr><td class="logo">@if(file_exists($logo))<img src="{{ $logo }}" alt="HN BLOOM">@else <strong>HN BLOOM</strong> @endif</td><td class="motto"><span>ENSEMBLE VERS<br>UN AVENIR MEILLEUR</span></td></tr></table>
<table class="title-table"><tr><td class="title">{{ $title }}</td><td style="width:18px"></td><td class="meta">N° : <b>{{ $num }}</b><br>Date : <b>{{ $date }}</b></td></tr></table>
<div class="section"><div class="section-title">{{ $type === 'commande' ? 'Destinataire' : 'Client' }}</div><table class="details">
<tr><td>Nom / Raison sociale :</td><td><strong>{{ $client['name'] ?? '—' }}</strong></td></tr>
@if(!empty($client['address']))<tr><td>Adresse :</td><td>{{ $client['address'] }}</td></tr>@endif
@if(!empty($client['city']))<tr><td>Ville :</td><td>{{ $client['city'] }}</td></tr>@endif
@if(!empty($client['ice']))<tr><td>ICE :</td><td>{{ $client['ice'] }}</td></tr>@endif
</table></div>
@if(!empty($doc['status']) && in_array($doc['status'],['brouillon','en_attente','refuse'],true))<div class="status">{{ strtoupper(str_replace('_',' ', $doc['status'])) }}</div>@endif
@if($type === 'recu')
<table class="lines"><thead><tr><th>Facture</th><th>Mode de paiement</th><th>Référence</th><th class="right">Montant reçu</th></tr></thead><tbody><tr>
<td>{{ $doc['invoice_number'] ?? '—' }}</td><td>{{ $doc['payment_method'] ?? '—' }}</td><td>{{ $doc['payment_reference'] ?? '—' }}</td><td class="right">{{ $money($doc['paid'] ?? 0) }}</td></tr></tbody></table>
<div class="conditions"><div class="heading">Détails du règlement</div>@if(!empty($doc['notes'])){!! nl2br(e($doc['notes'])) !!}@endif</div>
@else
<table class="lines"><thead><tr><th style="width:15%">Réf.</th><th style="width:{{ $hasPrices ? '37%' : '55%' }}">Désignation</th><th style="width:12%" class="right">Qté</th>
@if($hasPrices)<th class="right" style="width:18%">Prix unitaire HT</th><th class="right" style="width:18%">Total HT</th>@else<th>Unité</th>@endif
</tr></thead><tbody>
@forelse($items as $it)
<tr><td>{{ $it['sku'] ?? $it['ref'] ?? '—' }}</td><td>{{ $it['name'] ?? $it['designation'] ?? '—' }}</td><td class="right">{{ $it['quantity'] ?? '—' }}</td>
@if($hasPrices)<td class="right">{{ $money($it['unit_price'] ?? 0) }}</td><td class="right">{{ $money($it['total_ht'] ?? 0) }}</td>@else<td>{{ $it['unit'] ?? 'Pièce' }}</td>@endif</tr>
@empty<tr><td colspan="{{ $hasPrices ? 5 : 4 }}" style="height:34px;">—</td></tr>@endforelse
</tbody></table>
@if($hasPrices)<table class="totals"><tr><td>Total HT</td><td>{{ $money($doc['subtotal'] ?? 0) }}</td></tr><tr><td>TVA</td><td>{{ $money($doc['tax'] ?? 0) }}</td></tr><tr class="final"><td>Total TTC</td><td>{{ $money($doc['total'] ?? 0) }}</td></tr></table>@endif
@if(!empty($doc['terms']) || !empty($doc['notes']))<div class="conditions"><div class="heading">{{ $type === 'bon_livraison' ? 'Détails de livraison' : 'Conditions' }}</div>
@if(!empty($doc['terms'])){!! nl2br(e($doc['terms'])) !!}@endif
@if(!empty($doc['notes']))<p>{!! nl2br(e($doc['notes'])) !!}</p>@endif</div>@endif
@endif
</div>
<div class="footer"><table><tr><td><b>HN BLOOM</b><br>{{ $company['address'] ?? 'Casablanca, Maroc' }}</td><td><b>Téléphone</b><br>{{ $company['phone'] ?? '' }}<br>{{ $company['email'] ?? '' }}</td><td><b>Site web</b><br>{{ $company['website'] ?? '' }}</td></tr></table></div>
</body></html>
