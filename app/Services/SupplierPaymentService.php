<?php

namespace App\Services;

use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierPaymentService
{
    public function record(SupplierInvoice $invoice, float $amount, string $method, ?string $reference = null, ?string $chequeNumber = null, ?string $notes = null): SupplierPayment
    {
        return DB::transaction(function () use ($invoice, $amount, $method, $reference, $chequeNumber, $notes) {
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Le montant doit être positif.']);
            }

            $balance = round((float) $invoice->balance(), 2);
            if ($amount > $balance + 0.0001) {
                throw ValidationException::withMessages(['amount' => 'Le paiement ne peut pas dépasser le solde restant.']);
            }

            $payment = SupplierPayment::create([
                'supplier_invoice_id' => $invoice->id,
                'supplier_id' => $invoice->supplier_id,
                'user_id' => auth()->id(),
                'amount' => round($amount, 2),
                'method' => $method,
                'paid_at' => now()->toDateString(),
                'reference' => $reference,
                'cheque_number' => $chequeNumber,
                'notes' => $notes,
                'status' => 'valide',
                'number' => app(DocumentNumberService::class)->next('PF'),
            ]);

            $this->refresh($invoice);

            return $payment;
        });
    }

    public function cancel(SupplierPayment $payment, string $reason): SupplierPayment
    {
        return DB::transaction(function () use ($payment, $reason) {
            if ($payment->status !== 'valide') {
                throw ValidationException::withMessages(['payment' => 'Ce paiement n\'est pas valide.']);
            }

            $reason = trim($reason);
            if ($reason === '') {
                throw ValidationException::withMessages(['reason' => 'Le motif est obligatoire.']);
            }

            $payment->status = 'annule';
            $payment->cancel_reason = $reason;
            $payment->cancelled_at = now();
            $payment->cancelled_by = auth()->id();
            $payment->save();

            $this->refresh($payment->supplierInvoice);

            return $payment->fresh();
        });
    }

    public function refresh(SupplierInvoice $invoice): void
    {
        $invoice->refresh();
        $paid = (float) SupplierPayment::where('supplier_invoice_id', $invoice->id)
            ->where('status', 'valide')
            ->sum('amount');

        $invoice->amount_paid = round($paid, 2);
        if ($invoice->amount_paid <= 0) {
            $invoice->status = 'non_payee';
        } elseif (abs($invoice->amount_paid - $invoice->total_ttc) <= 0.0001) {
            $invoice->status = 'payee';
        } else {
            $invoice->status = 'partiellement_payee';
        }
        $invoice->save();
    }
}
