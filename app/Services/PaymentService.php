<?php

namespace App\Services;

use App\Events\PaymentCancelled;
use App\Events\PaymentRecorded;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private DocumentNumberService $numbers) {}

    public function record(Invoice $invoice, array $data, int $userId): Payment
    {
        $payment = DB::transaction(function () use ($invoice, $data, $userId) {
            $inv = Invoice::withoutGlobalScopes()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($inv->type !== 'facture' || ! in_array($inv->status, Invoice::OPEN, true)) {
                throw ValidationException::withMessages([
                    'amount' => 'Cette facture n\'accepte pas de paiement (brouillon, payée, annulée ou avoir).',
                ]);
            }

            $amount  = round((float) $data['amount'], 2);
            $balance = $inv->balance();

            if ($balance <= 0 || $amount <= 0 || $amount > $balance + 0.001) {
                throw ValidationException::withMessages([
                    'amount' => 'Le montant doit être entre 0,01 et le reste à payer (' . number_format($balance, 2, ',', ' ') . ' DH).',
                ]);
            }

            $isCheque = $data['method'] === 'cheque';

            $payment = Payment::create([
                'number'          => $this->numbers->next('PA'),
                'invoice_id'      => $inv->id,
                'client_id'       => $inv->client_id,
                'commercial_id'   => $inv->commercial_id,
                'user_id'         => $userId,
                'amount'          => $amount,
                'method'          => $data['method'],
                'paid_at'         => $data['paid_at'],
                'reference'       => $data['reference'] ?? null,
                'cheque_number'   => $isCheque ? ($data['cheque_number'] ?? null) : null,
                'cheque_bank'     => $isCheque ? ($data['cheque_bank'] ?? null) : null,
                'cheque_due_date' => $isCheque ? ($data['cheque_due_date'] ?? null) : null,
                'cheque_status'   => $isCheque ? 'en_attente' : null,
                'status'          => 'valide',
                'notes'           => $data['notes'] ?? null,
            ]);

            $this->refreshInvoice($inv);

            return $payment;
        });

        PaymentRecorded::dispatch($payment);

        return $payment;
    }

    public function cancel(Payment $payment, int $userId, string $reason, ?string $chequeStatus = null): Payment
    {
        $p = DB::transaction(function () use ($payment, $userId, $reason, $chequeStatus) {
            $p = Payment::withoutGlobalScopes()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($p->status === 'annule') {
                throw ValidationException::withMessages(['payment' => 'Ce paiement est déjà annulé.']);
            }

            $inv = Invoice::withoutGlobalScopes()->whereKey($p->invoice_id)->lockForUpdate()->firstOrFail();

            $p->update([
                'status'        => 'annule',
                'cancelled_at'  => now(),
                'cancelled_by'  => $userId,
                'cancel_reason' => $reason,
                'cheque_status' => $chequeStatus ?? $p->cheque_status,
            ]);

            $this->refreshInvoice($inv);

            return $p;
        });

        PaymentCancelled::dispatch($p);

        return $p;
    }

    /** encaisse | rejete (le rejet annule la paiement automatiquement) */
    public function updateCheque(Payment $payment, string $to, int $userId): Payment
    {
        if ($payment->method !== 'cheque' || $payment->status !== 'valide' || $payment->cheque_status !== 'en_attente') {
            throw ValidationException::withMessages(['payment' => 'Action impossible sur ce chèque.']);
        }

        if ($to === 'rejete') {
            return $this->cancel($payment, $userId, 'Chèque rejeté', 'rejete');
        }

        $cashed = DB::transaction(function () use ($payment) {
            $p = Payment::withoutGlobalScopes()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($p->status !== 'valide' || $p->cheque_status !== 'en_attente') {
                throw ValidationException::withMessages(['payment' => 'Ce chèque a déjà été traité.']);
            }

            $inv = Invoice::withoutGlobalScopes()->whereKey($p->invoice_id)->lockForUpdate()->firstOrFail();

            $p->update(['cheque_status' => 'encaisse']);

            // L'argent est entré : la facture peut passer à « Payée »
            $this->refreshInvoice($inv);

            return $p;
        });

        // Commission « montant fixe » : naît seulement quand l'argent est vraiment entré
        app(CommissionService::class)->onChequeCashed($cashed);

        return $cashed;
    }

    public function refreshInvoiceById(int $id): void
    {
        $invoice = Invoice::withoutGlobalScopes()->whereKey($id)->first();

        if ($invoice) {
            $this->refreshInvoice($invoice);
        }
    }

    /**
     * Recalcul depuis les paiements valides : source de vérité unique.
     *   amount_paid           = amount_cashed + amount_cheque_pending
     *   amount_cashed         = espèces + virements + chèques encaissés
     *   amount_cheque_pending = chèques valides pas encore encaissés
     */
    private function refreshInvoice(Invoice $inv): void
    {
        $net = max(0.0, (float) $inv->total_ttc - (float) $inv->creditedAmount());

        $valid = Payment::withoutGlobalScopes()
            ->where('invoice_id', $inv->id)->where('status', 'valide');

        $paid = round((float) (clone $valid)->sum('amount'), 2);

        $pending = round((float) (clone $valid)
            ->where('method', 'cheque')->where('cheque_status', 'en_attente')->sum('amount'), 2);

        $data = [
            'amount_paid'           => $paid,
            'amount_cashed'         => round($paid - $pending, 2),
            'amount_cheque_pending' => $pending,
        ];

        if (in_array($inv->status, Invoice::COUNTED, true)) {
            if ($net <= 0.005 && $paid == 0) {
                $data['status'] = 'annulee';
            } elseif ($paid >= $net - 0.005) {
                // Entièrement couverte : « Payée » seulement si aucun chèque n'est en attente
                $data['status'] = $pending > 0.005 ? 'cheque_en_attente' : 'payee';
            } elseif ($paid > 0) {
                $data['status'] = 'partiellement_payee';
            } else {
                $data['status'] = 'emise';
            }
        }

        $inv->update($data);
    }
}
