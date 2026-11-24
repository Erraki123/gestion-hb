@php
    $company = config('commerce.company', []);
    $hnbIconsDir = public_path('images/hnb-pdf-icons');
@endphp
<div class="footer">
    <table>
        <tr>
            <td style="width:48%;text-align:left;vertical-align:top;border:0;padding:0;">
                <strong>{{ strtoupper($company['name'] ?? 'HN BLOOM') }}</strong><br>
                @if(file_exists($hnbIconsDir . '/pin.png'))<img class="hnb-foot-icon" src="{{ $hnbIconsDir . '/pin.png' }}" alt="">@endif
                {{ $company['address'] ?? '' }}
                @if(config('commerce.invoice_footer'))<br>{{ config('commerce.invoice_footer') }}@endif
                @if(!empty($company['rc']))<br>RC : {{ $company['rc'] }}@endif
                @if(!empty($company['patente'])) · Patente : {{ $company['patente'] }}@endif
                @if(!empty($company['if'])) · IF : {{ $company['if'] }}@endif
                @if(!empty($company['cnss'])) · CNSS : {{ $company['cnss'] }}@endif
            </td>
            <td style="width:29%;text-align:center;vertical-align:top;border:0;padding:0;">
                @if(!empty($company['phone']))<div>@if(file_exists($hnbIconsDir . '/phone.png'))<img class="hnb-foot-icon" src="{{ $hnbIconsDir . '/phone.png' }}" alt="">@endif {{ $company['phone'] }}</div>@endif
                @if(!empty($company['email']))<div>@if(file_exists($hnbIconsDir . '/mail.png'))<img class="hnb-foot-icon" src="{{ $hnbIconsDir . '/mail.png' }}" alt="">@endif {{ $company['email'] }}</div>@endif
            </td>
            <td style="width:23%;text-align:right;vertical-align:top;border:0;padding:0;">
                @if(!empty($company['website']))<div>@if(file_exists($hnbIconsDir . '/globe.png'))<img class="hnb-foot-icon" src="{{ $hnbIconsDir . '/globe.png' }}" alt="">@endif {{ $company['website'] }}</div>@endif
                @if(!empty($company['ice']))<div>ICE : {{ $company['ice'] }}</div>@endif
                <div>Page <span class="pagenum"></span></div>
            </td>
        </tr>
    </table>
</div>
