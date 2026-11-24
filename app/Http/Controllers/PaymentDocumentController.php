<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentDocument;
use App\Services\PaymentDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentDocumentController extends Controller
{
    /** Ajouter un document à un paiement existant (le Global Scope du paiement donne 404 aux autres commerciaux). */
    public function store(Request $request, Payment $payment, PaymentDocumentService $service)
    {
        abort_unless($payment->status === 'valide', 403, 'Paiement annulé.');

        $data = $request->validate([
            'kind' => ['required', 'in:cheque,autre'],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        try {
            $service->add($payment, $request->file('file'), $data['kind'], auth()->id());
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['file' => 'Le fichier n\'a pas pu être sauvegardé.']);
        }

        return back()->with('success', 'Document ajouté.');
    }

    /** Ouvre le fichier (jamais d'URL publique : passage obligatoire par ce contrôle d'accès). */
    public function show(PaymentDocument $document)
    {
        Payment::findOrFail($document->payment_id); // 404 si le paiement n'appartient pas à ce commercial

        abort_unless(Storage::disk(PaymentDocumentService::DISK)->exists($document->path), 404);

        return Storage::disk(PaymentDocumentService::DISK)->response(
            $document->path,
            $document->original_name,
            ['X-Content-Type-Options' => 'nosniff'],
            'inline'
        );
    }

    public function destroy(PaymentDocument $document, PaymentDocumentService $service)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $service->delete($document);

        return back()->with('success', 'Document supprimé.');
    }
}
