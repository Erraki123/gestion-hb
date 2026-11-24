{{-- HN BLOOM V16 | PDF header, presentation only. --}}
@php
    $web = $web ?? false;
    $logoPath = public_path('images/hnbloom-premium-gold.png');
    if (!file_exists($logoPath)) $logoPath = public_path('images/hn-bloom-logo.png');
    if (!file_exists($logoPath)) $logoPath = public_path('images/logo.png');
    $logoSrc = $web ? asset('images/hnbloom-premium-gold.png') : $logoPath;
    $hasLogo = $web || file_exists($logoPath);
    $hnbIconsDir = public_path('images/hnb-pdf-icons');
@endphp
<div class="hn-doc-header">
    <div class="hn-upper-edge"></div>
    <table class="hn-brand-row"><tr>
        <td class="hn-brand-col" style="width:35%;">
            @if ($hasLogo)
                <img class="hn-main-logo" src="{{ $logoSrc }}" alt="HN BLOOM">
            @else
                <div class="hn-brand-fallback">HN BLOOM</div>
            @endif
        </td>
        <td class="hn-mantra-col" style="width:26%;">
            <div>INNOVATION</div><div>CONFIANCE</div><div>EXCELLENCE</div>
        </td>
        <td class="hn-brand-services-col" style="width:39%;">
            <table class="hn-icon-band"><tr>
                @foreach (['house','finger','camera','gate'] as $hnbIcon)
                    <td>
                        @if (file_exists($hnbIconsDir . '/' . $hnbIcon . '.png'))
                            <img src="{{ $hnbIconsDir . '/' . $hnbIcon . '.png' }}" alt="">
                        @endif
                    </td>
                @endforeach
            </tr></table>
            <div class="hn-services-title">DOMOTIQUE &nbsp;·&nbsp; SÉCURITÉ &nbsp;·&nbsp; ACCÈS</div>
        </td>
    </tr></table>

    <table class="hn-document-row"><tr>
        <td style="width:57%;vertical-align:middle;">
            <div class="hn-doc-title">{{ $title }}</div>
        </td>
        <td class="hn-meta-area" style="width:43%;vertical-align:middle;">
            <div class="hn-meta-label">DOCUMENT HN BLOOM</div>
            <div class="hn-meta-ref">{{ $ref }}</div>
            <div class="hn-meta-date">{{ $date }}</div>
        </td>
    </tr></table>
</div>
