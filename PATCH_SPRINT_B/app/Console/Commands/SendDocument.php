<?php

namespace App\Console\Commands;

use App\Services\DocumentSender;
use Illuminate\Console\Command;

class SendDocument extends Command
{
    protected $signature = 'documents:send {type : quote|invoice} {id} {--confirm : Explicitly confirm sending a real customer document}';
    protected $description = 'Envoie un devis ou une facture au client (e-mail + WhatsApp), pour tester';

    public function handle(): int
    {
        if (! config('sales_safety.allow_cli_document_send', false) || ! $this->option('confirm')) {
            $this->error('Envoi CLI bloque. Verification et autorisation explicites requises.');
            return self::FAILURE;
        }

        $doc = DocumentSender::find((string) $this->argument('type'), (int) $this->argument('id'));

        if (! $doc) {
            $this->error('Document introuvable.');

            return self::FAILURE;
        }

        $n = DocumentSender::queue($doc, true);
        $this->info("{$n} envoi(s) mis en file. Voir la table message_logs.");

        return self::SUCCESS;
    }
}
