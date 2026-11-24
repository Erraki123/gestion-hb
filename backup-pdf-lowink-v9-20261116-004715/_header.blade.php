{{-- HNB_LUXURY_CLEAN_V8 | Presentation only; same document payload --}}
@php
    $web = $web ?? false;
    $logoFs = null;
    foreach (['hnbloom-premium-gold.png','hn-bloom-logo.png','logo.png'] as $filename) {
        $candidate = public_path('images/' . $filename);
        if (file_exists($candidate)) { $logoFs = $candidate; break; }
    }
    $logoSrc = $web ? asset('images/hnbloom-premium-gold.png') : $logoFs;
    $artFs = public_path('images/hnb-luxury-v8-art.png');
@endphp
@if (file_exists($artFs))
    <div class="hnb-ornament"><img src="{{ $artFs }}" alt=""></div>
@endif
<div class="head-shell">
    <table class="head" cellspacing="0" cellpadding="0">
      <tr>
        <td class="brand-cell" style="width:32%; vertical-align:middle;">
          @if ($logoFs || $web)
            <img class="logo" src="{{ $logoSrc }}" alt="HN BLOOM">
          @else
            <div class="brand-text">HN BLOOM</div>
          @endif
        </td>
        <td class="brand-center" style="width:29%; text-align:center; vertical-align:middle;">
          <div class="brand-tagline">INNOVATION</div>
          <div class="brand-tagline">CONFIANCE</div>
          <div class="brand-tagline">EXCELLENCE</div>
        </td>
        <td class="brand-doc" style="width:39%; text-align:right; vertical-align:middle;">
          <div class="doc-title">{{ $title }}</div>
          <div class="doc-ref">{{ $ref }}</div>
          <div class="doc-date">{{ $date }}</div>
        </td>
      </tr>
    </table>
</div>
