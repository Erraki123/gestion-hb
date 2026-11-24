{{-- HN BLOOM V14 - luxury header, A4-safe --}}
@php
    $web = $web ?? false;
    $logoPath = public_path('images/hnbloom-premium-gold.png');
    if (!file_exists($logoPath)) $logoPath = public_path('images/hn-bloom-logo.png');
    if (!file_exists($logoPath)) $logoPath = public_path('images/logo.png');
    $logoSrc = $web ? asset('images/hnbloom-premium-gold.png') : $logoPath;
    $hasLogo = $web || file_exists($logoPath);
    $hnbIconsDir = public_path('images/hnb-pdf-icons');
@endphp
<div class="head">
  <div class="hnb-top-edge"></div>
  {{-- V15: miniature security iconography; decorative only, no fixed A4 art --}}
  <div class="hnb-industries">
    @foreach (['house', 'finger', 'camera', 'gate'] as $hnbIcon)
      @if (file_exists($hnbIconsDir . '/' . $hnbIcon . '.png'))
        <img src="{{ $hnbIconsDir . '/' . $hnbIcon . '.png' }}" alt="">
      @endif
    @endforeach
  </div>

  <table>
    <tr>
      <td style="width:31%;vertical-align:middle;">
        @if($hasLogo)
          <img class="logo" src="{{ $logoSrc }}" alt="HN BLOOM">
        @else
          <div class="brand-text">HN BLOOM</div>
        @endif
      </td>
      <td style="width:27%;vertical-align:middle;text-align:center;">
        <div class="brand-tagline">INNOVATION</div>
        <div class="brand-tagline">CONFIANCE</div>
        <div class="brand-tagline">EXCELLENCE</div>
      </td>
      <td class="right" style="width:42%;vertical-align:middle;">
        <div class="doc-title">{{ $title }}</div>
        <div class="doc-ref">{{ $ref }}</div>
        <div class="doc-date">{{ $date }}</div>
      </td>
    </tr>
  </table>
</div>
