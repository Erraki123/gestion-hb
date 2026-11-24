<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/** Passerelle WhatsApp : UltraMsg par défaut; possibilité de conserver WABridges. */
class WhatsAppService
{
    public function enabled(): bool
    {
        if (! config('notify.whatsapp.enabled')) return false;
        if (config('notify.whatsapp.provider', 'ultramsg') === 'ultramsg') {
            return filled(config('notify.whatsapp.ultramsg_instance'))
                && filled(config('notify.whatsapp.ultramsg_token'));
        }
        return config('notify.whatsapp.provider') === 'wabridges'
            && filled(config('notify.whatsapp.key'))
            && filled(config('notify.whatsapp.instance'));
    }

    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        $cc = (string) config('notify.whatsapp.country_code', '212');
        if ($digits === '') return null;
        if (str_starts_with($digits, '00')) $digits = substr($digits, 2);
        if (str_starts_with($digits, $cc) && strlen($digits) >= strlen($cc) + 8) {
            // Déjà international.
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = $cc . substr($digits, 1);
        } elseif (strlen($digits) === 9) {
            $digits = $cc . $digits;
        }
        return strlen($digits) >= 10 && strlen($digits) <= 15 ? $digits : null;
    }

    /** @return array{message_id?:string, timestamp?:int} */
    public function sendText(string $phone, string $body, string $idempotencyKey): array
    {
        if (! $this->enabled()) throw new WhatsAppException('WhatsApp non configuré.');
        $to = self::normalize($phone);
        if (! $to) throw new WhatsAppException('Numéro WhatsApp invalide.');
        if (trim($body) === '' || mb_strlen($body) > 4096) {
            throw new WhatsAppException('Message vide ou supérieur à 4096 caractères.');
        }

        if (config('notify.whatsapp.provider', 'ultramsg') === 'ultramsg') {
            $instance = (string) config('notify.whatsapp.ultramsg_instance');
            if (! preg_match('/^instance[0-9]+$/', $instance)) {
                throw new WhatsAppException('Instance ID UltraMsg invalide.');
            }
            $url = 'https://api.ultramsg.com/' . $instance . '/messages/chat';
            try {
                $r = Http::asForm()->acceptJson()->timeout(20)->post($url, [
                    'token' => config('notify.whatsapp.ultramsg_token'),
                    'to' => '+' . $to,
                    'body' => $body,
                ]);
            } catch (\Throwable $e) {
                // Ne pas réessayer automatiquement sur timeout: le message peut déjà être accepté.
                throw new WhatsAppException('Connexion UltraMsg incertaine: vérifier le journal du fournisseur.', false);
            }
            $json = $r->json();
            if ($r->successful() && is_array($json) && empty($json['error'])
                && empty($json['Error']) && ($json['sent'] ?? 'true') !== 'false'
                && ($json['sent'] ?? true) !== false) {
                return ['message_id' => (string) ($json['id'] ?? $json['message_id'] ?? '')];
            }
            throw new WhatsAppException('UltraMsg HTTP ' . $r->status() . ': envoi non confirmé.', false);
        }

        $url = rtrim((string) config('notify.whatsapp.base_url'), '/')
            . '/api/instances/' . rawurlencode((string) config('notify.whatsapp.instance')) . '/proxy/send/text';
        try {
            $r = Http::withToken((string) config('notify.whatsapp.key'))
                ->acceptJson()->withHeaders(['Idempotency-Key' => substr($idempotencyKey, 0, 120)])
                ->timeout(20)->post($url, ['chat' => $to, 'body' => $body]);
        } catch (\Throwable $e) {
            throw new WhatsAppException('Connexion WABridges incertaine.', false);
        }
        if ($r->successful()) return (array) $r->json();
        throw new WhatsAppException('WABridges HTTP ' . $r->status() . '.', false);
    }
}
