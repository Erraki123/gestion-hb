<?php

namespace App\Jobs;

use App\Mail\DocumentMail;
use App\Models\MessageLog;
use App\Services\DocumentSender;
use App\Services\WhatsAppException;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public function __construct(public int $logId) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(WhatsAppService $wa): void
    {
        $log = MessageLog::find($this->logId);

        if (! $log || $log->status === 'sent') {
            return;
        }

        $doc = DocumentSender::find((string) $log->document_type, (int) $log->document_id);

        if (! $doc) {
            $log->update(['status' => 'failed', 'error' => 'Document introuvable.']);

            return;
        }

        $type   = (string) $log->document_type;
        $number = DocumentSender::number($type, $doc);
        $label  = DocumentSender::label($type);
        $client = $doc->client;

        try {
            if ($log->channel === 'email') {
                Mail::to($log->recipient)->send(new DocumentMail(
                    $label, $number, (string) ($client?->name ?? ''),
                    DocumentSender::total($doc), DocumentSender::extra($type, $doc),
                    DocumentSender::pdf($type, $doc), DocumentSender::filename($type, $doc),
                ));

                $log->update(['status' => 'sent', 'error' => null, 'sent_at' => now()]);

                return;
            }

            // WhatsApp : message + lien PDF sécurisé (valable quelques semaines)
            $lines = array_filter([
                'Bonjour ' . ($client?->name ?? '') . ' 👋',
                '',
                "Voici votre {$label} n° {$number} : " . number_format(DocumentSender::total($doc), 2, ',', ' ') . ' DH TTC.',
                DocumentSender::extra($type, $doc),
                '',
                '📄 ' . DocumentSender::signedUrl($type, $doc),
                '',
                '— ' . config('commerce.company.name'),
            ], fn ($l) => $l !== null);

            $res = $wa->sendText((string) $log->recipient, implode("\n", $lines), 'doc-log-' . $log->id);

            $log->update([
                'status'      => 'sent',
                'error'       => null,
                'sent_at'     => now(),
                'provider_id' => $res['message_id'] ?? null,
            ]);
        } catch (WhatsAppException $e) {
            $log->update(['error' => mb_substr($e->getMessage(), 0, 500)]);

            if (! $e->retryable) {
                $log->update(['status' => 'failed']);

                return; // inutile de réessayer : il faut corriger la cause
            }

            throw $e;
        } catch (\Throwable $e) {
            $log->update(['error' => mb_substr($e->getMessage(), 0, 500)]);

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        MessageLog::whereKey($this->logId)->update([
            'status' => 'failed',
            'error'  => mb_substr($e->getMessage(), 0, 500),
        ]);
    }
}
