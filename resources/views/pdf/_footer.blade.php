{{-- HNB V22 footer: data always sourced from actual CRM configuration --}}
@php($hn22company = config('commerce.company', []))
<div class="footer hn22-footer">
    <table class="hn22-contact-table">
        <tr>
            <td class="hn22-contact-left" style="width:46%;">
                <strong>{{ strtoupper($hn22company['name'] ?? 'HN BLOOM') }}</strong>
                @if(!empty($hn22company['address']))
                    <div class="hn22-address">{{ $hn22company['address'] }}</div>
                @endif
            </td>
            <td style="width:27%;" class="hn22-contact-middle">
                @if(!empty($hn22company['phone']))<div><span class="hn22-contact-symbol">&#9742;</span> {{ $hn22company['phone'] }}</div>@endif
                @if(!empty($hn22company['email']))<div><span class="hn22-contact-symbol">@</span> {{ $hn22company['email'] }}</div>@endif
            </td>
            <td style="width:27%;" class="hn22-contact-right">
                @if(!empty($hn22company['website']))<div>{{ $hn22company['website'] }}</div>@endif
                @if(!empty($hn22company['ice']))<div>ICE : {{ $hn22company['ice'] }}</div>@endif
            </td>
        </tr>
    </table>
    <div class="hn22-legal">
        @if(config('commerce.invoice_footer')){{ config('commerce.invoice_footer') }} &nbsp;·&nbsp; @endif
        @if(!empty($hn22company['rc']))RC : {{ $hn22company['rc'] }} &nbsp;·&nbsp; @endif
        @if(!empty($hn22company['patente']))Patente : {{ $hn22company['patente'] }} &nbsp;·&nbsp; @endif
        @if(!empty($hn22company['if']))IF : {{ $hn22company['if'] }} &nbsp;·&nbsp; @endif
        @if(!empty($hn22company['cnss']))CNSS : {{ $hn22company['cnss'] }}@endif
        <span class="hn22-page">Page <span class="pagenum"></span></span>
    </div>
    <div class="hn22-wave"><img src="{{ public_path('images/hnb-pdf-v22/footer-wave-subtle.png') }}" alt=""></div>
</div>
