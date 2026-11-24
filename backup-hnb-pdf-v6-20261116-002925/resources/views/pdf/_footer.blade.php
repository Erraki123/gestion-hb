@php
    $company = config('commerce.company', []);
@endphp
<div class="footer">
    <table>
        <tr>
            <td style="width:44%; text-align:left; border:0; padding:0; vertical-align:top;">
                <strong>{{ strtoupper($company['name'] ?? 'HN BLOOM') }}</strong><br>
                {{ $company['address'] ?? '' }}
            </td>
            <td style="width:28%; text-align:center; border:0; padding:0; vertical-align:top;">
                @if (!empty($company['phone']))<div>{{ $company['phone'] }}</div>@endif
                @if (!empty($company['email']))<div>{{ $company['email'] }}</div>@endif
            </td>
            <td style="width:28%; text-align:right; border:0; padding:0; vertical-align:top;">
                @if (!empty($company['website']))<div>{{ $company['website'] }}</div>@endif
                @if (!empty($company['ice']))<div>ICE : {{ $company['ice'] }}</div>@endif
                <div>Page <span class="pagenum"></span></div>
            </td>
        </tr>
    </table>
</div>
