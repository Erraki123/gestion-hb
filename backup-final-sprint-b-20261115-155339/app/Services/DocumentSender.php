<?php

namespace App\Services;

use App\Jobs\SendDocumentJob;
use App\Models\Invoice;
use App\Models\MessageLog;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/**
 * Envoie un devis ou une facture au client (e-mail + WhatsApp).
 *   DocumentSender::queue($quote);          // respecte les interrupteurs du .env
 *   DocumentSender::queue($quote, true);    // envoi manuel : ignore l'interrupteur « auto »
 */
class DocumentSender
{
    public static function typeOf(Model $doc): ?string
    {
        return match (true) {
            $doc instanceof Quote   => 'quote',
            $doc instanceof Invoice => 'invoice',
            default                 => null,
        };
    }

    public static function find(string $type, int $id): ?Model
    {
        return match ($type) {
            'quote'   => Quote::withoutGlobalScope('owner')->find($id),
            'invoice' => Invoice::withoutGlobalScope('owner')->find($id),
            default   => null,
        };
    }

    public static function queue(Model $doc, bool $manual = false): int
    {
        $type = self::typeOf($doc);

        if (! $type) {
            return 0;
        }

        if ($type === 'quote' && ! $doc->canSendToClient()) {
            return 0; // Devis avec remise/prix spécial non validé : jamais envoyé
        }

        if ($type === 'invoice' && ($doc->type !== 'facture' || ! $doc->number)) {
            return 0; // pas d'avoir ni de brouillon
        }

        if (! $manual && ! config("notify.auto.{$type}")) {
            return 0;
        }

        $doc->loadMissing('client');
        $client = $doc->client;
        // Droit d'opposition : ne rien expédier aux contacts désinscrits.
        if ($client && $client->prospectDetail()->where('do_not_contact', true)->exists()) return 0;
        $n = 0;

        if (config('notify.email.enabled')) {
            $n += self::dispatchFor('email', $type, $doc, $client?->email, $manual);
        }

        if (app(WhatsAppService::class)->enabled()) {
            $n += self::dispatchFor('whatsapp', $type, $doc, $client?->phone, $manual);
        }

        return $n;
    }

    private static function dispatchFor(string $channel, string $type, Model $doc, ?string $to, bool $manual = false): int
    {
        // Envoi automatique : une seule fois par document et par canal
        if (! $manual && MessageLog::where('document_type', $type)->where('document_id', $doc->id)
                ->where('channel', $channel)->whereIn('status', ['queued', 'sent'])->exists()) {
            return 0;
        }

        $log = MessageLog::create([
            'channel'       => $channel,
            'kind'          => $type,
            'recipient'     => $to,
            'subject'       => self::label($type) . ' ' . self::number($type, $doc),
            'document_type' => $type,
            'document_id'   => $doc->id,
            'status'        => $to ? 'queued' : 'skipped',
            'error'         => $to ? null : ($channel === 'email' ? 'Le client n\'a pas d\'e-mail.' : 'Le client n\'a pas de téléphone.'),
        ]);

        if (! $to) {
            return 0;
        }

        $job = new SendDocumentJob($log->id);

        if (! config('notify.queue')) {
            dispatch_sync($job);

            return 1;
        }

        $pending = SendDocumentJob::dispatch($log->id)->afterCommit();

        if ($channel === 'whatsapp') {
            $pending->delay(now()->addSeconds(random_int(
                (int) config('notify.whatsapp.delay_min', 5),
                max((int) config('notify.whatsapp.delay_max', 25), (int) config('notify.whatsapp.delay_min', 5))
            )));
        }

        return 1;
    }

    /* ---------- contenu ---------- */

    public static function label(string $type): string
    {
        return $type === 'quote' ? 'Devis' : 'Facture';
    }

    public static function number(string $type, Model $doc): string
    {
        return $type === 'invoice' ? $doc->displayNumber() : (string) $doc->number;
    }

    public static function filename(string $type, Model $doc): string
    {
        return preg_replace('/[^A-Za-z0-9._-]+/', '-', self::number($type, $doc)) . '.pdf';
    }

    public static function pdf(string $type, Model $doc): string
    {
        if ($type === 'invoice') {
            $doc->loadMissing(['client', 'commercial', 'items', 'parent', 'payments']);
            $view = 'invoices.pdf';
            $data = ['invoice' => $doc];
        } else {
            $doc->loadMissing(['client', 'items', 'order']);
            $view = 'quotes.pdf';
            $data = ['quote' => $doc];
        }

        if (! view()->exists($view)) {
            throw new \RuntimeException("Vue PDF introuvable : {$view}");
        }

        return Pdf::loadView($view, $data)->setPaper('a4')->output();
    }

    public static function signedUrl(string $type, Model $doc): string
    {
        return URL::temporarySignedRoute(
            'documents.public',
            now()->addDays((int) config('notify.link_days', 30)),
            ['type' => $type, 'id' => $doc->id]
        );
    }

    public static function total(Model $doc): float
    {
        return (float) $doc->total_ttc;
    }

    /** « Échéance » (facture) ou « Valable jusqu'au » (devis) */
    public static function extra(string $type, Model $doc): ?string
    {
        if ($type === 'invoice') {
            return $doc->due_date ? 'Échéance : ' . $doc->due_date->format('d/m/Y') : null;
        }

        return $doc->valid_until ? 'Valable jusqu\'au ' . $doc->valid_until->format('d/m/Y') : null;
    }
}
