<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Quote;
use App\Services\DocumentSender;

/** Bouton « Envoyer au client » (e-mail + WhatsApp). Le scope du commercial s'applique : il ne voit que ses documents. */
class DocumentSendController extends Controller
{
    public function store(string $type, int $id)
    {
        // Only the director/admin may manually release client documents.
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);

        $doc = match ($type) {
            'quote'   => Quote::findOrFail($id),
            'invoice' => Invoice::findOrFail($id),
            default   => abort(404),
        };

        if ($doc instanceof Quote && ! $doc->canSendToClient()) {
            return back()->withErrors(['send' => 'Devis avec remise ou prix spécial : validation administrative obligatoire avant envoi.']);
        }

        if (! app(\App\Services\DocumentReleaseApproval::class)->canRelease($doc)) {
            return back()->withErrors(['send' => 'Validation de l\'envoi par le directeur obligatoire (ou document modifie depuis validation).']);
        }

        $n = DocumentSender::queue($doc, true);

        if ($n === 0) {
            return back()->withErrors(['send' => 'Aucun envoi : vérifiez que le client a un e-mail / un téléphone et que les envois sont activés (.env).']);
        }

        return back()->with('success', 'Envoi lancé (' . $n . ' message(s)).');
    }
}
