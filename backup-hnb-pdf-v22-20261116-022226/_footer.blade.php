{{-- HNB PDF V21: full genuine company details, separated from decorative artwork. --}}
@php($company = config('commerce.company', []))
<div class="footer hn-footer">
    <div class="hn-foot-company-row">
        <div class="hn-footer-brand">{{ strtoupper($company['name'] ?? 'HN BLOOM') }}</div>
        @if(!empty($company['address']))
            <div class="hn-footer-address">{{ $company['address'] }}</div>
        @endif
    </div>
    <table class="hn-foot-contacts"><tr>
        <td style="width:33%;">
            @if(!empty($company['phone']))
                <span class="hn-foot-icon">&#9742;</span> {{ $company['phone'] }}
            @endif
        </td>
        <td style="width:39%;">
            @if(!empty($company['email']))
                <span class="hn-foot-icon">@</span> {{ $company['email'] }}
            @endif
        </td>
        <td style="width:28%;">
            @if(!empty($company['website']))
                <span class="hn-foot-icon">&#9678;</span> {{ $company['website'] }}
            @endif
        </td>
    </tr></table>
    <div class="hn-foot-legal">
        @if(config('commerce.invoice_footer')){{ config('commerce.invoice_footer') }}<span class="hn-dot"> · </span>@endif
        @if(!empty($company['ice']))ICE : {{ $company['ice'] }}<span class="hn-dot"> · </span>@endif
        @if(!empty($company['rc']))RC : {{ $company['rc'] }}<span class="hn-dot"> · </span>@endif
        @if(!empty($company['patente']))Patente : {{ $company['patente'] }}<span class="hn-dot"> · </span>@endif
        @if(!empty($company['if']))IF : {{ $company['if'] }}<span class="hn-dot"> · </span>@endif
        @if(!empty($company['cnss']))CNSS : {{ $company['cnss'] }}@endif
        <span class="hn-foot-page">Page <span class="pagenum"></span></span>
    </div>
    <img class="hn-footer-wave" src="{{ public_path('images/hnb-pdf-v21/footer-wave-subtle.png') }}" alt="">
</div>
