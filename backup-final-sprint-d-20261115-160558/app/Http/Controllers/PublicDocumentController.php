<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\DocumentSender;

/** PDF du devis / de la facture via un lien signé et temporaire (lien envoyé par WhatsApp) */
class PublicDocumentController extends Controller
{
    public function show(string $type, int $id)
    {
        $doc = DocumentSender::find($type, $id);

        abort_unless($doc, 404);

        if ($doc instanceof Invoice) {
            abort_unless($doc->type === 'facture' && $doc->number, 404);
        }

        return response(DocumentSender::pdf($type, $doc), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . DocumentSender::filename($type, $doc) . '"',
            'X-Robots-Tag'        => 'noindex',
        ]);
    }
}
