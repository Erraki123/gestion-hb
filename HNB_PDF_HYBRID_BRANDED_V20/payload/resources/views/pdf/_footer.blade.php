{{-- HN BLOOM V20: decorative footer image + REAL CRM configured company data --}}
@php($company = config('commerce.company', []))
<div class="footer hn-footer hnb-v20-footer">
    <img class="hnb-v20-footer-art" src="{{ public_path('images/hnb-pdf-hybrid-v20/footer-ornament.png') }}" alt="">
    <table class="hnb-v20-footer-data"><tr>
        <td style="width:48%; vertical-align:top; padding-right:9px;">
            <strong>{{ strtoupper($company['name'] ?? 'HN BLOOM') }}</strong>
            @if(!empty($company['address']))<div>{{ $company['address'] }}</div>@endif
            @if(config('commerce.invoice_footer'))<div class="hnb-v20-legal">{{ config('commerce.invoice_footer') }}</div>@endif
        </td>
        <td style="width:25%; vertical-align:top; padding:0 7px;">
            @if(!empty($company['phone']))<div>{{ $company['phone'] }}</div>@endif
            @if(!empty($company['email']))<div>{{ $company['email'] }}</div>@endif
        </td>
        <td style="width:27%; vertical-align:top; padding-left:7px;">
            @if(!empty($company['website']))<div>{{ $company['website'] }}</div>@endif
            @if(!empty($company['ice']))<div>ICE : {{ $company['ice'] }}</div>@endif
            <div>Page <span class="pagenum"></span></div>
        </td>
    </tr></table>
    <div class="hnb-v20-identifiers">
        @if(!empty($company['rc']))RC : {{ $company['rc'] }}@endif
        @if(!empty($company['patente'])) · Patente : {{ $company['patente'] }}@endif
        @if(!empty($company['if'])) · IF : {{ $company['if'] }}@endif
        @if(!empty($company['cnss'])) · CNSS : {{ $company['cnss'] }}@endif
    </div>
</div>
