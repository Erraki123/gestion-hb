{{-- HNB Executive V18 Footer. All company values from configuration. --}}
@php
  $company = config('commerce.company', []);
  $hnbIconsDir = public_path('images/hnb-pdf-icons');
@endphp
<div class="footer hn-footer">
  <div class="hn-footer-line"></div>
  <table><tr>
    <td style="width:44%;vertical-align:middle;">
      <strong>{{ strtoupper($company['name'] ?? 'HN BLOOM') }}</strong>
      <div>@if(file_exists($hnbIconsDir.'/pin.png'))<img class="hn-small-icon" src="{{ $hnbIconsDir.'/pin.png' }}" alt="">@endif {{ $company['address'] ?? '' }}</div>
      @if(config('commerce.invoice_footer'))<div>{{ config('commerce.invoice_footer') }}</div>@endif
      <div>@if(!empty($company['rc']))RC : {{ $company['rc'] }}@endif @if(!empty($company['patente'])) · Patente : {{ $company['patente'] }}@endif @if(!empty($company['if'])) · IF : {{ $company['if'] }}@endif @if(!empty($company['cnss'])) · CNSS : {{ $company['cnss'] }}@endif</div>
    </td>
    <td class="hn-foot-contact" style="width:28%;vertical-align:middle;">
      @if(!empty($company['phone']))<div>@if(file_exists($hnbIconsDir.'/phone.png'))<img class="hn-small-icon" src="{{ $hnbIconsDir.'/phone.png' }}" alt="">@endif {{ $company['phone'] }}</div>@endif
      @if(!empty($company['email']))<div>@if(file_exists($hnbIconsDir.'/mail.png'))<img class="hn-small-icon" src="{{ $hnbIconsDir.'/mail.png' }}" alt="">@endif {{ $company['email'] }}</div>@endif
    </td>
    <td class="hn-foot-web" style="width:28%;vertical-align:middle;">
      @if(!empty($company['website']))<div>@if(file_exists($hnbIconsDir.'/globe.png'))<img class="hn-small-icon" src="{{ $hnbIconsDir.'/globe.png' }}" alt="">@endif {{ $company['website'] }}</div>@endif
      @if(!empty($company['ice']))<div>ICE : {{ $company['ice'] }}</div>@endif
      <div>Page <span class="pagenum"></span></div>
    </td>
  </tr></table>
</div>
