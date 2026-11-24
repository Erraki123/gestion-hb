<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $doc['title'] }} — {{ $doc['number'] }}</title>
@include('pdf.chrome.style')
</head>
<body>
<div class="sheet">
  <header class="masthead hnb25-masthead">
  <div class="hnb25-top">
    <div class="hnb25-brand" aria-label="HN BLOOM">
      <svg class="hnb25-arch" viewBox="0 0 320 45" preserveAspectRatio="none" aria-hidden="true"><path d="M12 34 C64 0 232 0 309 35"/></svg>
      <div class="hnb25-brandname">HN BLOOM</div>
      <div class="hnb25-tagline">Smart Solutions for a Safer &amp; Smarter World</div>
    </div>
    <div class="hnb25-banner">
      <div class="hnb25-icons">
        <div class="hnb25-icon" aria-label="Domotique"><svg viewBox="0 0 48 48" aria-hidden="true"><path d="M6 22 24 8l18 14M12 20v20h24V20M17 29q7-7 14 0M20 34q4-4 8 0M24 39h.01"/></svg></div>
        <div class="hnb25-icon" aria-label="Empreinte"><svg viewBox="0 0 48 48" aria-hidden="true"><path d="M10 24C7 13 16 6 24 6c9 0 16 7 16 17M15 42c4-9-2-18 3-25 6-8 19-5 20 8M21 42c3-7-1-14 2-18 3-4 8-1 8 4M9 32c-3-13 3-22 15-23M27 42c4-7 4-11 4-16M35 40c4-6 6-12 6-19M15 34c3-8-1-17 9-19"/></svg></div>
        <div class="hnb25-icon" aria-label="Camera"><svg viewBox="0 0 48 48" aria-hidden="true"><path d="m7 18 30-8 5 13-30 8zM18 30l-6 7M30 28l6 10M12 40h27M7 18l-3 7"/></svg></div>
        <div class="hnb25-icon" aria-label="Controle d'acces"><svg viewBox="0 0 48 48" aria-hidden="true"><path d="M7 7h11v34H7zM30 7h11v34H30zM18 17h12M18 28h12M22 20l7 4-7 4M12 12h.01M36 12h.01"/></svg></div>
      </div>
    </div>
  </div>
  <div class="hnb25-description">Domotique <b>•</b> Contrôle d’accès <b>•</b> Sécurité électronique <b>•</b> Solutions industrielles</div>
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
      <div class="party-title"><span class="label-icon">▤</span> ÉMETTEUR</div>
      <div class="party-body">
        <strong>{{ $doc['issuer']['name'] ?? 'HN BLOOM' }}</strong>
        @if(!empty($doc['issuer']['address']))<p>{{ $doc['issuer']['address'] }}</p>@endif
        @if(!empty($doc['issuer']['phone']))<p>☎ {{ $doc['issuer']['phone'] }}</p>@endif
        @if(!empty($doc['issuer']['email']))<p>✉ {{ $doc['issuer']['email'] }}</p>@endif
        @if(!empty($doc['issuer']['ice']))<p>ICE : {{ $doc['issuer']['ice'] }}</p>@endif
      </div>
    </div>
    <div class="party-card">
      <div class="party-title"><span class="label-icon">♙</span> CLIENT</div>
      <div class="party-body">
        <strong>{{ $doc['client']['name'] ?? '—' }}</strong>
        @if(!empty($doc['client']['company']))<p>{{ $doc['client']['company'] }}</p>@endif
        @if(!empty($doc['client']['address']))<p>{{ $doc['client']['address'] }}</p>@endif
        @if(!empty($doc['client']['ice']))<p>ICE : {{ $doc['client']['ice'] }}</p>@endif
      </div>
    </div>
  </section>
  @if($doc['subtitle']) <div class="subnote">{{ $doc['subtitle'] }}</div> @endif

  @if(count($doc['items']))
    <table class="line-table">
      <thead><tr>
        <th style="width:12%">RÉF.</th>
        <th>DÉSIGNATION</th>
        <th style="width:13%">QTÉ</th>
        @if($doc['kind'] !== 'delivery')
          <th style="width:13%">PRIX HT</th>
          <th style="width:9%">TVA</th>
          <th style="width:10%">REMISE</th>
          <th style="width:15%">TOTAL HT</th>
        @else
          <th style="width:13%">UNITÉ</th>
        @endif
      </tr></thead>
      <tbody>
      @foreach($doc['items'] as $item)
        <tr>
          <td class="small">{{ $item['sku'] ?: '—' }}</td>
          <td><strong class="item-name">{{ $item['name'] }}</strong></td>
          <td class="num">{{ $item['quantity'] }} @if($doc['kind'] !== 'delivery')<small>{{ $item['unit'] }}</small>@endif</td>
          @if($doc['kind'] !== 'delivery')
            <td class="num">{{ $item['unit_price'] }}</td>
            <td class="num">{{ $item['tax_rate'] }} %</td>
            <td class="num">{{ $item['discount'] }} %</td>
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
            <div class="note-title">◈ &nbsp;{{ $section[0] }}</div>
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
    <div class="footer-block address"><span class="footer-ico">⌖</span><div><strong>{{ $company['name'] ?? 'HN BLOOM' }}</strong><span>{{ $company['address'] ?? '' }}</span></div></div>
    <div class="footer-block"><span class="footer-ico">☎</span><div><span>{{ $company['phone'] ?? '' }}</span></div></div>
    <div class="footer-block"><span class="footer-ico">✉</span><div><span>{{ $company['email'] ?? '' }}</span></div></div>
    <div class="footer-block"><span class="footer-ico">◎</span><div><span>{{ $company['website'] ?? '' }}</span></div></div>
  </div>
  <div class="footer-sweep"></div>
</footer>
</body>
</html>
