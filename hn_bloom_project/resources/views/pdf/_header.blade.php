@php $logo = public_path('images/logo.png'); @endphp
<div class="topbar">
    <table>
        <tr>
            <td style="width: 66px; vertical-align: middle;">
                @if (file_exists($logo)) <img class="logo" src="{{ $logo }}"> @endif
            </td>
            <td style="vertical-align: middle; padding-left: 8px;">
                <div class="brand">{{ strtoupper(config('commerce.company.name')) }}</div>
                <div class="sub">
                    {{ config('commerce.company.address') }}
                    @if (config('commerce.company.phone')) <br>Tél : {{ config('commerce.company.phone') }} @endif
                    @if (config('commerce.company.ice')) &nbsp;·&nbsp; ICE : {{ config('commerce.company.ice') }} @endif
                </div>
            </td>
            <td class="right" style="vertical-align: middle; width: 40%;">
                <div class="doc-title">{{ $title }}</div>
                <div class="doc-ref">{{ $ref }}</div>
                <div class="doc-date">{{ $date }}</div>
            </td>
        </tr>
    </table>
</div>
<div class="goldline"></div>
