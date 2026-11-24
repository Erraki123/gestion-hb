{{-- Keep actual company details and legal footer config. --}}
<div class="footer">
    <table>
      <tr>
        <td style="width:58%;vertical-align:top;text-align:left;">
          <strong>{{ strtoupper(config('commerce.company.name', 'HN BLOOM')) }}</strong>
          @if(config('commerce.company.address'))<div>{{ config('commerce.company.address') }}</div>@endif
          @if(config('commerce.invoice_footer'))<div>{{ config('commerce.invoice_footer') }}</div>@endif
        </td>
        <td style="width:34%;vertical-align:top;text-align:left;">
          @if(config('commerce.company.phone'))<div>{{ config('commerce.company.phone') }}</div>@endif
          @if(config('commerce.company.email'))<div>{{ config('commerce.company.email') }}</div>@endif
          @if(config('commerce.company.website'))<div>{{ config('commerce.company.website') }}</div>@endif
          @if(config('commerce.company.ice'))<div>ICE : {{ config('commerce.company.ice') }}</div>@endif
        </td>
        <td style="width:8%;vertical-align:top;text-align:right;">Page <span class="pagenum"></span></td>
      </tr>
    </table>
</div>
