<div style="font-family: Arial, Helvetica, sans-serif; color:#1f1f1f; max-width:560px; margin:0 auto; padding:24px;">
    <h2 style="margin:0 0 12px; color:#8a6a12;">{{ config('commerce.company.name') }}</h2>
    <p>Bonjour {{ $clientName }},</p>
    <p>Veuillez trouver ci-joint votre <strong>{{ strtolower($label) }} n° {{ $number }}</strong>
       d'un montant de <strong>{{ number_format($total, 2, ',', ' ') }} DH TTC</strong>.</p>
    @if ($extra)<p style="color:#555;">{{ $extra }}</p>@endif
    <p>Nous restons à votre disposition pour toute question.</p>
    <p style="margin-top:24px;">Cordialement,<br>{{ config('commerce.company.name') }}</p>
</div>
