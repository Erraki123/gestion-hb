<div class="footer">
    <table>
        <tr>
            <td style="text-align:left; border:0; padding:0;">
                <strong>{{ strtoupper(config('commerce.company.name', 'HN BLOOM')) }}</strong>
                @if (config('commerce.company.address')) · {{ config('commerce.company.address') }} @endif
                <br>
                {{ config('commerce.invoice_footer') }}
                @if (config('commerce.company.phone')) · Tél : {{ config('commerce.company.phone') }} @endif
                @if (config('commerce.company.ice')) · ICE : {{ config('commerce.company.ice') }} @endif
            </td>
            <td style="width:9%; text-align:right; border:0; padding:0; color:#77736b;">Page <span class="pagenum"></span></td>
        </tr>
    </table>
</div>
