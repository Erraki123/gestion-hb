@php
    $company = config('commerce.company', []);
@endphp
<div class="footer">
    <table>
        <tr>
            <td style="width:48%;text-align:left;vertical-align:top;border:0;padding:0;">
                <strong>{{ strtoupper($company['name'] ?? 'HN BLOOM') }}</strong><br>
                {{ $company['address'] ?? '' }}
                @if(config('commerce.invoice_footer'))<br>{{ config('commerce.invoice_footer') }}@endif
                @if(!empty($company['rc']))<br>RC : {{ $company['rc'] }}@endif
                @if(!empty($company['patente'])) · Patente : {{ $company['patente'] }}@endif
                @if(!empty($company['if'])) · IF : {{ $company['if'] }}@endif
                @if(!empty($company['cnss'])) · CNSS : {{ $company['cnss'] }}@endif
            </td>
            <td style="width:29%;text-align:center;vertical-align:top;border:0;padding:0;">
                @if(!empty($company['phone']))<div>{{ $company['phone'] }}</div>@endif
                @if(!empty($company['email']))<div>{{ $company['email'] }}</div>@endif
            </td>
            <td style="width:23%;text-align:right;vertical-align:top;border:0;padding:0;">
                @if(!empty($company['website']))<div>{{ $company['website'] }}</div>@endif
                @if(!empty($company['ice']))<div>ICE : {{ $company['ice'] }}</div>@endif
                <div>Page <span class="pagenum"></span></div>
            </td>
        </tr>
    </table>
</div>
