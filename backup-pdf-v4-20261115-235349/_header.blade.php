
@php
    $web = $web ?? false;
    $logoFs = public_path('images/hn-bloom-logo.png');
    $logoSrc = $web ? asset('images/hn-bloom-logo.png') : $logoFs;
@endphp

<div class="hnb-top-decoration"></div>

<div class="hnb-header">
    <table>
        <tr>
            <td class="hnb-brand">
                @if(file_exists($logoFs) || $web)
                    <img src="{{ $logoSrc }}" class="hnb-logo" alt="HN BLOOM">
                @else
                    <div class="hnb-logo-fallback">HN BLOOM</div>
                @endif
            </td>
            <td class="hnb-slogan">
                ENSEMBLE VERS<br>
                UN AVENIR MEILLEUR
                <div class="hnb-gold-line"></div>
            </td>
        </tr>
    </table>
</div>

<div class="hnb-document-heading">
    <table>
        <tr>
            <td class="hnb-document-title">{{ $title }}</td>
            <td class="hnb-document-details">
                <strong>{{ $ref }}</strong>
                <div>{{ $date }}</div>
            </td>
        </tr>
    </table>
</div>
