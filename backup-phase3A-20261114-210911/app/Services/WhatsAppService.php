<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/** Client minimal pour WABridges : POST /api/instances/{instance}/proxy/send/text */
class WhatsAppService
{
    public function enabled(): bool
    {
        return (bool) config('notify.whatsapp.enabled')
            && config('notify.whatsapp.key')
            && config('notify.whatsapp.instance');
    }

    /** 0764437164 / +212 764-437-164 / 00212764437164  ->  212764437164 */
    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        $cc     = (string) config('notify.whatsapp.country_code', '212');

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, $cc) && strlen($digits) >= strlen($cc) + 8) {
            // déjà au format international
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = $cc . substr($digits, 1);
        } elseif (strlen($digits) === 9) {
            $digits = $cc . $digits;
        }

        return strlen($digits) >= 10 && strlen($digits) <= 15 ? $digits : null;
    }

    /** @return array{message_id?: string, timestamp?: int} */
    public function sendText(string $phone, string $body, string $idempotencyKey): array
    {
        if (! $this->enabled()) {
            throw new WhatsAppException('WhatsApp n\'est pas activé (voir .env).');
        }

        $chat = self::normalize($phone);

        if (! $chat) {
            throw new WhatsAppException('Numéro WhatsApp invalide : ' . $phone);
        }

        $url = rtrim((string) config('notify.whatsapp.base_url'), '/')
            . '/api/instances/' . rawurlencode((string) config('notify.whatsapp.instance')) . '/proxy/send/text';

        try {
            $r = Http::withToken((string) config('notify.whatsapp.key'))
                ->acceptJson()
                ->withHeaders(['Idempotency-Key' => substr($idempotencyKey, 0, 120)])
                ->timeout(20)
                ->post($url, ['chat' => $chat, 'body' => $body]);
        } catch (\Throwable $e) {
            throw new WhatsAppException('Connexion à WABridges impossible : ' . $e->getMessage(), true);
        }

        if ($r->successful()) {
            return (array) $r->json();
        }

        $code = (string) $r->json('code');
        $msg  = 'WhatsApp ' . $r->status() . ($code ? " ({$code})" : '') . ' : ' . (string) $r->json('error');

        // 429 / 503 / 5xx / conflit d'idempotence : on peut réessayer. Le reste : il faut corriger.
        $retry = in_array($r->status(), [429, 500, 502, 503], true) || $code === 'idempotency_conflict';

        throw new WhatsAppException($msg, $retry);
    }
}
