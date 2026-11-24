<div class="footer">
    {{ config('commerce.invoice_footer') }}<br>
    {{ config('commerce.company.name') }} · {{ config('commerce.company.address') }}
    @if (config('commerce.company.ice')) · ICE : {{ config('commerce.company.ice') }} @endif
    · Page <span class="pagenum"></span>
</div>
