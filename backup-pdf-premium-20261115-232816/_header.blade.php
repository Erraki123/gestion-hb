{{-- HN BLOOM Premium PDF header. Shared by quote, invoice, receipt and order documents. --}}
@php
    $web = $web ?? false;
    $logoFs = public_path('images/logo.png');
    $wmFs = public_path('images/logo-watermark.png');
    $logoSrc = $web ? asset('images/logo.png') : $logoFs;
    $wmSrc = $web ? asset('images/logo-watermark.png') : $wmFs;
    $hasLogo = $web || file_exists($logoFs);
    $hasWm = $web || file_exists($wmFs);
@endphp
@if ($hasWm)
    <div class="watermark"><img src="{{ $wmSrc }}" alt=""></div>
@endif
<div class="head">
    <table>
        <tr>
            <td style="width:28%; vertical-align:middle;">
                @if ($hasLogo)
                    <img class="logo" src="{{ $logoSrc }}" alt="HN BLOOM">
                @else
                    <div class="brand-text">HN BLOOM</div>
                @endif
            </td>
            <td style="width:42%; vertical-align:middle; text-align:center;">
                <div class="brand-tagline">Innovation&nbsp; • &nbsp;Qualité&nbsp; • &nbsp;Confiance</div>
            </td>
            <td class="right" style="width:30%; vertical-align:middle;">
                <div class="doc-title">{{ $title }}</div>
                <div class="doc-ref">{{ $ref }}</div>
                <div class="doc-date">{{ $date }}</div>
            </td>
        </tr>
    </table>
</div>
