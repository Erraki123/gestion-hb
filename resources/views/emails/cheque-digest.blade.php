<div style="font-family: Arial, Helvetica, sans-serif; color:#1f1f1f; max-width:640px; margin:0 auto; padding:24px;">
    <h2 style="margin:0 0 4px; color:#8a6a12;">🔔 Chèques à encaisser</h2>
    <p style="margin:0 0 16px; color:#555;">{{ now()->format('d/m/Y') }} · Bonjour {{ $who }}</p>

    @foreach ($groups as $g)
        <h3 style="margin:18px 0 6px;">{{ $g['title'] }}</h3>
        <table style="width:100%; border-collapse:collapse; font-size:14px;">
            @foreach ($g['rows'] as $r)
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:6px 4px;"><strong>{{ $r['client'] }}</strong><br>
                        <span style="color:#777; font-size:12px;">{{ $r['invoice'] }} · chèque {{ $r['number'] }}</span></td>
                    <td style="padding:6px 4px; text-align:right; white-space:nowrap;">
                        {{ number_format($r['amount'], 2, ',', ' ') }} DH<br>
                        <span style="color:#777; font-size:12px;">éch. {{ $r['due'] }} ({{ $r['when'] }})</span></td>
                </tr>
            @endforeach
        </table>
    @endforeach

    <p style="margin-top:20px;"><strong>Total : {{ $count }} chèque(s) · {{ number_format($sum, 2, ',', ' ') }} DH</strong></p>
    <p style="color:#777; font-size:12px;">Connectez-vous pour marquer chaque chèque « encaissé » ou « rejeté ».</p>
</div>
