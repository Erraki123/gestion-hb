<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentDocument;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Enregistre les fichiers d'un paiement sur le disque PRIVÉ (storage/app/private). */
class PaymentDocumentService
{
    public const DISK = 'local';

    /** Règle de validation commune (jpg, png, pdf, 5 Mo). */
    public const RULE = ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'];

    /** À appeler APRÈS l'enregistrement du paiement. Renvoie un message d'erreur, ou null si tout va bien. */
    public function attachFromRequest(Payment $payment, Request $request, int $userId): ?string
    {
        try {
            if ($request->hasFile('cheque_scan')) {
                $this->add($payment, $request->file('cheque_scan'), 'cheque', $userId);
            }

            if ($request->hasFile('other_file')) {
                $this->add($payment, $request->file('other_file'), 'autre', $userId);
            }
        } catch (\Throwable $e) {
            Log::error('Échec de sauvegarde du document de paiement', ['payment' => $payment->id, 'error' => $e->getMessage()]);

            return 'Le paiement est enregistré, mais le fichier n\'a pas pu être sauvegardé. Ajoutez-le depuis la liste des paiements.';
        }

        return null;
    }

    public function add(Payment $payment, UploadedFile $file, string $kind, int $userId): PaymentDocument
    {
        $ext  = strtolower($file->guessExtension() ?: 'bin');
        $name = Str::uuid() . '.' . $ext;
        $path = $file->storeAs('payment-documents/' . $payment->invoice_id, $name, self::DISK);

        if ($path === false) {
            throw new \RuntimeException('Stockage impossible.');
        }

        return PaymentDocument::create([
            'payment_id'    => $payment->id,
            'invoice_id'    => $payment->invoice_id,
            'kind'          => in_array($kind, ['cheque', 'autre'], true) ? $kind : 'autre',
            'path'          => $path,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
            'mime'          => (string) ($file->getMimeType() ?: 'application/octet-stream'),
            'size'          => (int) $file->getSize(),
            'uploaded_by'   => $userId,
        ]);
    }

    public function delete(PaymentDocument $document): void
    {
        Storage::disk(self::DISK)->delete($document->path);
        $document->delete();
    }
}
