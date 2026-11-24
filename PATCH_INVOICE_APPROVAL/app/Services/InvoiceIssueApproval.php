<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceIssueApproval
{
    public function fingerprint(Invoice $invoice): string
    {
        $invoice->load(['items']);
        $items = $invoice->items->sortBy('id')->values()->map(fn ($line) => [
            'product_id' => $line->product_id,
            'name' => $line->name,
            'sku' => $line->sku,
            'unit' => $line->unit,
            'quantity' => (string) $line->quantity,
            'unit_price' => (string) $line->unit_price,
            'discount_percent' => (string) $line->discount_percent,
            'tax_rate' => (string) $line->tax_rate,
            'line_total_ht' => (string) $line->line_total_ht,
        ])->all();

        return hash('sha256', json_encode([
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'order_id' => $invoice->order_id,
            'commercial_id' => $invoice->commercial_id,
            'type' => $invoice->type,
            'subtotal_ht' => (string) $invoice->subtotal_ht,
            'tax_amount' => (string) $invoice->tax_amount,
            'total_ttc' => (string) $invoice->total_ttc,
            'transport_amount' => (string) $invoice->transport_amount,
            'installation_amount' => (string) $invoice->installation_amount,
            'due_date' => optional($invoice->due_date)->format('Y-m-d'),
            'notes' => $invoice->notes,
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public function request(Invoice $invoice, int $userId): void
    {
        if ($invoice->type !== 'facture' || $invoice->status !== 'brouillon') {
            throw ValidationException::withMessages(['approval' => 'Seul un brouillon de facture peut etre soumis.']);
        }
        DB::table('invoice_issue_approvals')->updateOrInsert(
            ['invoice_id' => $invoice->id],
            [
                'fingerprint' => $this->fingerprint($invoice),
                'status' => 'pending',
                'requested_by' => $userId,
                'requested_at' => now(),
                'decided_by' => null,
                'decided_at' => null,
                'comment' => null,
                'updated_at' => now(),
            ]
        );
    }

    public function status(Invoice $invoice): string
    {
        $row = DB::table('invoice_issue_approvals')->where('invoice_id', $invoice->id)->first();
        if (! $row) return 'not_requested';
        return hash_equals((string) $row->fingerprint, $this->fingerprint($invoice)) ? (string) $row->status : 'outdated';
    }
}
