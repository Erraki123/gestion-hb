<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $doc['title'] }} — {{ $doc['number'] }}</title>
@include('pdf.chrome.style')
</head>
<body>
{{-- HNB_SVG_PDF_ICONS_V1: vector icons, no extra fonts --}}
<div class="sheet">
  @if(!empty($studioUnapproved))
    <div class="studio-unapproved" role="note">APERÇU INTERNE — EN ATTENTE DE VALIDATION DIRECTION — NE PAS ENVOYER AU CLIENT</div>
  @endif
  <header class="masthead">
    <div class="brand-row">
      <div class="brand-col">
        @if($logoData)
          <img class="brand-logo" src="{{ $logoData }}" alt="HN BLOOM">
        @else
          <div class="brand-word">HN <span>BLOOM</span></div>
        @endif
        <div class="brand-tag">INNOVATION <b>•</b> CONFIANCE <b>•</b> EXCELLENCE</div>
      </div>
      <div class="service-band">
        <div class="service-icon">
          <svg viewBox="0 0 32 32"><path d="M4 15 16 5l12 10M8 13v14h16V13M12 19c2.2-2.5 5.8-2.5 8 0M14 22c1.1-1.1 2.9-1.1 4 0M16 25h.01"/></svg>
          <span>DOMOTIQUE</span>
        </div>
        <div class="service-icon">
          <svg viewBox="0 0 32 32"><path d="M8 12c1-10 15-11 17 1M5 16c-1-7 5-13 12-13M11 29c4-8-2-15 4-19 7-5 11 6 7 12M13 15c3-4 9-1 7 5M8 21c1-5 0-11 7-14M16 26c2-4 1-8 1-10M23 27c3-4 5-8 5-13"/></svg>
          <span>ACCÈS</span>
        </div>
        <div class="service-icon">
          <svg viewBox="0 0 32 32"><path d="m4 10 20-5 4 10-20 5zM23 18l-7 4m5-4 3 7M9 23h14v4H9zM8 19l-3 4"/></svg>
          <span>SÉCURITÉ</span>
        </div>
        <div class="service-icon">
          <svg viewBox="0 0 32 32"><path d="M5 4v24M27 4v24M5 8h22M5 24h22M12 9v13M18 10l5 6-5 6"/></svg>
          <span>INDUSTRIE</span>
        </div>
      </div>
    </div>
    <div class="services-description">Domotique <i>•</i> Contrôle d’accès <i>•</i> Sécurité électronique <i>•</i> Solutions industrielles</div>
  </header>

  <section class="document-head">
    <div class="document-ribbon"><span>{{ $doc['title'] }}</span></div>
    <div class="document-metadata">
      <div class="doc-meta-line"><span>Référence :</span><strong>{{ $doc['number'] }}</strong></div>
      <div class="doc-meta-line"><span>Date :</span><strong>{{ $doc['date'] }}</strong></div>
    </div>
  </section>

  @if($doc['stamp']) <div class="state-label">{{ $doc['stamp'] }}</div> @endif
  <section class="parties">
    <div class="party-card">
      <div class="party-title"><span class="label-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c79a2e" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:inline-block;vertical-align:-2px;"><rect x="4" y="2.5" width="16" height="19" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg></span> ÉMETTEUR</div>
      <div class="party-body">
        <strong>{{ $doc['issuer']['name'] ?? 'HN BLOOM' }}</strong>
        @if(!empty($doc['issuer']['address']))<p>{{ $doc['issuer']['address'] }}</p>@endif
        @if(!empty($doc['issuer']['phone']))<p><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#9b793b" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:inline-block;vertical-align:-1px;margin-right:3px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 3.09 5.18 2 2 0 0 1 5.08 3h3a2 2 0 0 1 2 1.72c.12.96.34 1.91.65 2.81a2 2 0 0 1-.45 2.11L9.09 10.91a16 16 0 0 0 4 4l1.27-1.19a2 2 0 0 1 2.11-.45c.9.31 1.85.53 2.81.65A2 2 0 0 1 22 16.92Z"/></svg>{{ $doc['issuer']['phone'] }}</p>@endif
        @if(!empty($doc['issuer']['email']))<p><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#9b793b" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:inline-block;vertical-align:-1px;margin-right:3px;"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>{{ $doc['issuer']['email'] }}</p>@endif
        @if(!empty($doc['issuer']['ice']))<p>ICE : {{ $doc['issuer']['ice'] }}</p>@endif
      </div>
    </div>
    <div class="party-card">
      <div class="party-title"><span class="label-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c79a2e" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:inline-block;vertical-align:-2px;"><circle cx="12" cy="7.5" r="3.5"/><path d="M5 21v-1c0-4 3-6.3 7-6.3s7 2.3 7 6.3v1"/></svg></span>{{ in_array($doc['kind'], ['purchase_order', 'goods_receipt'], true) ? 'FOURNISSEUR' : 'CLIENT' }}</div>
      <div class="party-body">
        <strong>{{ $doc['client']['name'] ?? '—' }}</strong>
        @if(!empty($doc['client']['company']))<p>{{ $doc['client']['company'] }}</p>@endif
        @if(!empty($doc['client']['address']))<p>{{ $doc['client']['address'] }}</p>@endif
        @if(!empty($doc['client']['ice']))<p>ICE : {{ $doc['client']['ice'] }}</p>@endif
      </div>
    </div>
  </section>
  @if($doc['subtitle']) <div class="subnote">{{ $doc['subtitle'] }}</div> @endif

  @php
    // V28: Show Remise only if at least one line actually has a non-zero discount.
    $hnbShowRemise = in_array($doc['kind'], ['quote', 'invoice', 'order', 'purchase_order'], true)
        && collect($doc['items'] ?? [])->contains(function ($line) {
            $value = preg_replace('/[^0-9,.-]/', '', (string) ($line['discount'] ?? '0'));
            $value = str_replace(',', '.', (string) $value);
            return abs((float) $value) > 0.00001;
        });
  @endphp

  {{-- HNB_V30_TVA_SUMMARY_ONLY: TVA excluded from product table; preserved in totals. --}}
  @if(count($doc['items']))
    <table class="line-table">
      <thead><tr>
        <th style="width:14%">RÉF.</th>
        <th>DÉSIGNATION</th>
        <th style="width:14%">QTÉ</th>
        @if(!in_array($doc['kind'], ['delivery', 'goods_receipt'], true))
          <th style="width:16%">PRIX HT</th>
          @if($hnbShowRemise)<th style="width:10%">REMISE</th>@endif
          <th style="width:19%">TOTAL HT</th>
        @else
          <th style="width:13%">UNITÉ</th>
        @endif
      </tr></thead>
      <tbody>
      @foreach($doc['items'] as $item)
        <tr>
          <td class="small">{{ $item['sku'] ?: '—' }}</td>
          <td><strong class="item-name">{{ $item['name'] }}</strong></td>
          <td class="num">{{ $item['quantity'] }} @if(!in_array($doc['kind'], ['delivery', 'goods_receipt'], true))<small>{{ $item['unit'] }}</small>@endif</td>
          @if(!in_array($doc['kind'], ['delivery', 'goods_receipt'], true))
            <td class="num">{{ $item['unit_price'] }}</td>
            @if($hnbShowRemise)<td class="num">{{ $item['discount'] }} %</td>@endif
            <td class="num boldnum">{{ $item['total'] }}</td>
          @else
            <td>{{ $item['unit'] }}</td>
          @endif
        </tr>
      @endforeach
      </tbody>
    </table>
  @endif

  @if(count($doc['totals']) || count($doc['sections']))
    <div class="summary-row">
      <div class="notes-zone">
        @foreach($doc['sections'] as $section)
          <div class="note-card">
            <div class="note-title"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#b28a50" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:inline-block;vertical-align:-1px;margin-right:4px;"><path d="m12 2 9 10-9 10-9-10Z"/><path d="m8 12 4 4 4-4-4-4-4 4Z"/></svg>&nbsp;{{ $section[0] }}</div>
            <div class="note-content">{{ $section[1] }}</div>
          </div>
        @endforeach
      </div>
      @if(count($doc['totals']))
        <div class="summary-box">
          @foreach($doc['totals'] as $total)
            <div class="summary-line {{ ($total[2] ?? '') === 'grand' ? 'summary-grand' : (($total[2] ?? '') === 'strong' ? 'summary-strong' : '') }}">
              <span>{{ $total[0] }}</span><strong>{{ $total[1] }}</strong>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  @endif

  @if(!empty($doc['receipt']))
    <div class="section-heading">DÉTAILS DU RÈGLEMENT</div>
    <div class="receipt-grid">
      @foreach($doc['receipt'] as $r)
        @if(!empty($r[1]))<div class="receipt-cell"><span>{{ $r[0] }}</span><strong>{{ $r[1] }}</strong></div>@endif
      @endforeach
    </div>
  @endif

  @if(count($doc['payments']))
    <div class="section-heading">PAIEMENTS ENREGISTRÉS</div>
    <table class="payment-table">
      <thead><tr><th>DATE</th><th>RÉFÉRENCE</th><th>MODE</th><th>MONTANT</th></tr></thead>
      <tbody>
        @foreach($doc['payments'] as $p)
          <tr><td>{{ $p['date'] }}</td><td>{{ $p['number'] }}</td><td>{{ $p['method'] }}</td><td class="num">{{ $p['amount'] }}</td></tr>
        @endforeach
      </tbody>
    </table>
  @endif
  @if(count($doc['signatures']))
    <div class="signature-row">
      @foreach($doc['signatures'] as $sign)
        <div><span>{{ $sign }}</span><div class="signature-line"></div></div>
      @endforeach
    </div>
  @endif
</div>
<footer class="page-footer">
  <div class="footer-top"></div>
  <div class="footer-flex">
    <div class="footer-block address"><span class="footer-ico"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#b28a50" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:inline-block;vertical-align:-2px;"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></span><div><strong>{{ $company['name'] ?? 'HN BLOOM' }}</strong><span>{{ $company['address'] ?? '' }}</span></div></div>
    <div class="footer-block"><span class="footer-ico"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#b28a50" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:inline-block;vertical-align:-2px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 3.09 5.18 2 2 0 0 1 5.08 3h3a2 2 0 0 1 2 1.72c.12.96.34 1.91.65 2.81a2 2 0 0 1-.45 2.11L9.09 10.91a16 16 0 0 0 4 4l1.27-1.19a2 2 0 0 1 2.11-.45c.9.31 1.85.53 2.81.65A2 2 0 0 1 22 16.92Z"/></svg></span><div><span>{{ $company['phone'] ?? '' }}</span></div></div>
    <div class="footer-block"><span class="footer-ico"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#b28a50" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:inline-block;vertical-align:-2px;"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span><div><span>{{ $company['email'] ?? '' }}</span></div></div>
    <div class="footer-block"><span class="footer-ico"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#b28a50" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:inline-block;vertical-align:-2px;"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c-3 3-4 6-4 9s1 6 4 9M12 3c3 3 4 6 4 9s-1 6-4 9"/></svg></span><div><span>{{ $company['website'] ?? '' }}</span></div></div>
  </div>
  <div class="footer-sweep"></div>
</footer>
</body>
</html>
