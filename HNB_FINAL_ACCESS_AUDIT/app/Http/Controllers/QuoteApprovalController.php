<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteApprovalController extends Controller
{
    public function request(Request $request, Quote $quote)
    {
        abort_unless($request->user()->isAdmin() || $quote->commercial_id === $request->user()->id, 403);
        DB::transaction(function () use ($quote, $request) {
            $quote = Quote::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            if (! $quote->isOpen() || ! $quote->requires_approval || $quote->approval_status === 'approved') {
                throw ValidationException::withMessages(['approval' => 'Ce devis ne nécessite pas cette demande.']);
            }
            $quote->update([
                'approval_status' => 'pending',
                'approval_requested_by' => $request->user()->id,
                'approval_requested_at' => now(),
                'approval_decided_by' => null, 'approval_decided_at' => null, 'approval_comment' => null,
            ]);
        });
        return back()->with('success', 'Demande envoyée à l’administration.');
    }

    public function decide(Request $request, Quote $quote)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($data['decision'] === 'rejected' && blank($data['comment'] ?? null)) {
            throw ValidationException::withMessages(['comment' => 'Indiquez la raison du refus.']);
        }
        DB::transaction(function () use ($quote, $request, $data) {
            $quote = Quote::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            if (! $quote->isOpen() || ! $quote->requires_approval || $quote->approval_status !== 'pending') {
                throw ValidationException::withMessages(['approval' => 'Aucune demande de validation en attente.']);
            }
            $quote->update([
                'approval_status' => $data['decision'], 'approval_decided_by' => $request->user()->id,
                'approval_decided_at' => now(), 'approval_comment' => $data['comment'] ?? null,
            ]);
        });
        return back()->with('success', 'Décision enregistrée.');
    }
}
