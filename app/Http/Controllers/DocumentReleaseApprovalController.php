<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Quote;
use App\Services\DocumentReleaseApproval;
use Illuminate\Http\Request;

class DocumentReleaseApprovalController extends Controller
{
    private function resolve(string $type, int $id): Quote|Invoice
    {
        return match ($type) {
            'quote' => Quote::findOrFail($id),
            'invoice' => Invoice::findOrFail($id),
            default => abort(404),
        };
    }

    public function request(string $type, int $id, DocumentReleaseApproval $approval)
    {
        $document = $this->resolve($type, $id);
        try {
            $approval->request($document, (int) auth()->id());
        } catch (\DomainException $e) {
            return back()->withErrors(['release' => $e->getMessage()]);
        }
        return back()->with('success', 'Demande de validation de l\'envoi enregistree.');
    }

    public function decide(Request $request, string $type, int $id, DocumentReleaseApproval $approval)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'comment' => ['nullable', 'string', 'max:1000', 'required_if:decision,rejected'],
        ]);
        try {
            $approval->decide($this->resolve($type, $id), (int) auth()->id(),
                $validated['decision'], $validated['comment'] ?? null);
        } catch (\DomainException $e) {
            return back()->withErrors(['release' => $e->getMessage()]);
        }
        return back()->with('success', 'Decision de validation enregistree.');
    }
}
