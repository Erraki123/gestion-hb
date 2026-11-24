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
        $doc = match ($type) {
            'quote'   => Quote::findOrFail($id),
            'invoice' => Invoice::findOrFail($id),
            default   => abort(404),
        };

        $n = DocumentSender::queue($doc, true);

        if ($n === 0) {
            return back()->withErrors(['send' => 'Aucun envoi : vérifiez que le client a un e-mail / un téléphone et que les envois sont activés (.env).']);
        }

        return back()->with('success', 'Envoi lancé (' . $n . ' message(s)).');
    }
}
