<?php

namespace App\Listeners;

use App\Events\InvoiceIssued;
use App\Models\Invoice;
use App\Services\DocumentSender;

/** Facture émise -> e-mail + WhatsApp au client (si NOTIFY_AUTO_INVOICE=true) */
class SendInvoiceToClient
{
    public function handle(InvoiceIssued $event): void
    {
        $invoice = null;

        foreach (get_object_vars($event) as $value) {
            if ($value instanceof Invoice) {
                $invoice = $value;
                break;
            }
        }

        if (! $invoice) {
            return;
        }

        try {
            DocumentSender::queue($invoice);
        } catch (\Throwable $e) {
            report($e); // un souci d'envoi ne doit jamais bloquer la facture
        }
    }
}
