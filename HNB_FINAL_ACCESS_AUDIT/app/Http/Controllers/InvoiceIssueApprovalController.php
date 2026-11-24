<?php

namespace App\Http\Controllers;

use App\Events\InvoiceIssued;
use App\Models\Invoice;
use App\Services\InvoiceIssueApproval;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceIssueApprovalController extends Controller
{
    public function request(Request $request, Invoice $invoice, InvoiceIssueApproval $approvals)
    {
        abort_unless($request->user()->isAdmin() || (int) $invoice->commercial_id === (int) $request->user()->id, 403);
        $approvals->request($invoice, $request->user()->id);
        return back()->with('success', 'Facture transmise a la direction pour validation.');
    }

    public function decide(Request $request, Invoice $invoice, InvoiceIssueApproval $approvals, InvoiceService $service)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($data['decision'] === 'rejected' && blank($data['comment'] ?? null)) {
            throw ValidationException::withMessages(['comment' => 'Indiquez le motif du refus.']);
        }

        $issued = DB::transaction(function () use ($invoice, $approvals, $service, $request, $data) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $row = DB::table('invoice_issue_approvals')->where('invoice_id', $invoice->id)->lockForUpdate()->first();
            if ($invoice->type !== 'facture' || $invoice->status !== 'brouillon' || ! $row || $row->status !== 'pending'
                || ! hash_equals((string) $row->fingerprint, $approvals->fingerprint($invoice))) {
                throw ValidationException::withMessages(['approval' => 'Demande absente, deja traitee ou facture modifiee.']);
            }

            // Do not auto-validate any delivery or customer order on behalf of the director.
            // If an order-backed invoice requires a delivery, that workflow must be completed separately.
            if ($data['decision'] === 'approved' && $invoice->order_id !== null) {
                $order = $invoice->order;
                if ($order && $order->status === 'confirmee' && ! $order->deliveries()->where('status', 'valide')->exists()) {
                    throw ValidationException::withMessages(['approval' => 'Validez d abord la livraison de cette commande avant d emettre la facture.']);
                }
            }
            // Set approval inside the same DB transaction BEFORE issuance.
            // InvoiceService validates the recorded fingerprint and status for every issuance path.
            DB::table('invoice_issue_approvals')->where('invoice_id', $invoice->id)->update([
                'status' => $data['decision'],
                'decided_by' => $request->user()->id,
                'decided_at' => now(),
                'comment' => $data['comment'] ?? null,
                'updated_at' => now(),
            ]);
            if ($data['decision'] !== 'approved') return null;
            $issuedInvoice = $service->issueLocked($invoice->id, $request->user()->id);
            // The issue operation may adjust a past due date; the approved version is the issued version.
            DB::table('invoice_issue_approvals')->where('invoice_id', $invoice->id)->update([
                'fingerprint' => $approvals->fingerprint($issuedInvoice), 'updated_at' => now(),
            ]);
            return $issuedInvoice;
        });
        if ($issued) InvoiceIssued::dispatch($issued);
        return back()->with('success', $issued ? 'Facture approuvee et emise.' : 'Facture refusee. Le brouillon reste bloque.');
    }
}
