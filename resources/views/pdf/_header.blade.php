<div style="border-top: 5px solid #C9A227; padding-top: 10px;">
    <table>
        <tr>
            <td style="width: 60%;">
                <div class="brand">{{ config('commerce.company.name') }}</div>
                <div class="muted">
                    {{ config('commerce.company.address') }}
                    @if (config('commerce.company.phone')) · Tél : {{ config('commerce.company.phone') }} @endif
                    @if (config('commerce.company.ice')) · ICE : {{ config('commerce.company.ice') }} @endif
                </div>
            </td>
            <td class="right">
                <div class="doc-title">{{ $title }}</div>
                <div><strong>{{ $ref }}</strong></div>
                <div class="muted">{{ $date }}</div>
            </td>
        </tr>
    </table>
</div>