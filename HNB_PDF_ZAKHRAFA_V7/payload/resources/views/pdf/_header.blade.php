{{-- HN BLOOM Premium WOW PDF header --}}
@php
    $web = $web ?? false;
    $logoCandidates = [
        public_path('images/hnbloom-premium-gold.png'),
        public_path('images/hn-bloom-logo.png'),
        public_path('images/logo.png'),
        public_path('images/hnbloom_logo_reference.png'),
    ];

    $logoFs = null;
    foreach ($logoCandidates as $candidate) {
        if (file_exists($candidate)) { $logoFs = $candidate; break; }
    }

    $wmCandidates = [
        public_path('images/hnbloom-premium-gold.png'),
        public_path('images/logo-watermark.png'),
        public_path('images/hnbloom_logo_reference.png'),
        public_path('images/logo.png'),
    ];

    $wmFs = null;
    foreach ($wmCandidates as $candidate) {
        if (file_exists($candidate)) { $wmFs = $candidate; break; }
    }

    $logoSrc = $web ? asset('images/hnbloom-premium-gold.png') : $logoFs;
    $wmSrc   = $web ? asset('images/hnbloom-premium-gold.png') : $wmFs;
    $hasLogo = $web || !empty($logoFs);
    $hasWm   = $web || !empty($wmFs);
@endphp

@if ($hasWm)
    <div class="watermark"><img src="{{ $wmSrc }}" alt=""></div>
@endif






{{-- Printed page filigree: decorative asset, no business data changed --}}
@if (file_exists(public_path('images/hnb-v7-zakhrafa-frame.png')))
    <div class="hnb-v7-page-ornament"><img src="{{ public_path('images/hnb-v7-zakhrafa-frame.png') }}" alt=""></div>
@endif
<div class="head-shell">
    <div class="head-topline"></div><div class="hnb-v6-kicker">HN BLOOM &nbsp; / &nbsp; DOCUMENT OFFICIEL</div>
    <div class="head-corner"></div>

    <div class="head">
        <table>
            <tr>
                <td style="width:30%; vertical-align:top;">
                    @if ($hasLogo)
                        <img class="logo" src="{{ $logoSrc }}" alt="HN BLOOM">
                    @else
                        <div class="brand-text">HN BLOOM</div>
                    @endif
                </td>
                <td style="width:28%; vertical-align:middle; text-align:center;">
                    <div class="brand-tagline">INNOVATION</div>
                    <div class="brand-tagline">CONFIANCE</div>
                    <div class="brand-tagline">EXCELLENCE</div>
                </td>
                <td class="right" style="width:42%; vertical-align:top;">
                    <div class="doc-title">{{ $title }}</div>
                    <div class="doc-ref">{{ $ref }}</div>
                    <div class="doc-date">{{ $date }}</div>
                </td>
            </tr>
        </table>
    </div>
</div>
