<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    /** عمولة "نسبة من الفاتورة" عند الإصدار */
    public function onInvoiceIssued(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $inv = $this->lockInvoice($invoice->id);

            if ($inv->type !== 'facture' || ! in_array($inv->status, Invoice::COUNTED, true)) {
                return;
            }

            $rule = CommissionRule::currentFor($inv->commercial_id);
            if (! $rule || $rule->method !== 'invoice_percent') {
                return;
            }

            if ($this->activeExists('invoice', $inv->id)) {
                return;
            }

            $base = $this->base((float) $inv->total_ttc, $inv);
            $this->create($rule, 'invoice', $inv, null, $base, round($base * (float) $rule->value / 100, 2));
        });
    }

    /** الفاتورة أُلغيت بـ avoir: تُعكس كل عمولاتها */
    public function onInvoiceCancelled(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $inv = $this->lockInvoice($invoice->id);

            Commission::withoutGlobalScopes()
                ->where('invoice_id', $inv->id)
                ->whereIn('kind', ['invoice', 'fixed', 'payment'])
                ->where('status', '!=', 'annulee')
                ->get()
                ->each(fn ($c) => $this->reverse($c, 'Facture annulée par avoir'));
        });
    }

    public function onCreditNoteIssued(Invoice $credit): void
    {
        DB::transaction(function () use ($credit) {
            if ($credit->parent_id === null) {
                return;
            }

            $parent = $this->lockInvoice($credit->parent_id);
            $invoiceCommission = Commission::withoutGlobalScopes()
                ->where('invoice_id', $parent->id)
                ->where('kind', 'invoice')
                ->where('status', '!=', 'annulee')
                ->first();

            if (! $invoiceCommission) {
                return;
            }

            $base = $this->base((float) $credit->total_ttc, $credit);
            $amount = -round($base * (float) $invoiceCommission->rate_value / 100, 2);

            if ($amount === 0.0) {
                return;
            }

            Commission::withoutGlobalScopes()->create([
                'user_id'     => $invoiceCommission->user_id,
                'rule_id'     => $invoiceCommission->rule_id,
                'kind'        => 'ajustement',
                'parent_id'   => $invoiceCommission->id,
                'invoice_id'  => $credit->id,
                'payment_id'  => null,
                'method'      => $invoiceCommission->method,
                'rate_value'  => $invoiceCommission->rate_value,
                'base_amount' => round($base, 2),
                'amount'      => $amount,
                'status'      => 'en_attente',
                'computed_at' => now(),
                'note'        => 'Avoir ' . $credit->number,
            ]);
        });
    }

    /** عمولة "نسبة من الدفعات" + تحديث المبلغ الثابت */
    public function onPaymentRecorded(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $inv = $this->lockInvoice($payment->invoice_id);
            $p   = Payment::withoutGlobalScopes()->findOrFail($payment->id);

            if ($p->status === 'valide') {
                $rule = CommissionRule::currentFor($inv->commercial_id);

                // « % des paiements » et « % selon les produits » : la commission naît à chaque paiement reçu
                if ($rule && in_array($rule->method, ['payments_percent', 'product_percent'], true)
                    && ! $this->activeExists('payment', $inv->id, $p->id)) {
                    $base = $this->base((float) $p->amount, $inv);
                    $rate = $rule->method === 'product_percent'
                        ? $this->productRate($rule, $inv)
                        : (float) $rule->value;

                    $this->create($rule, 'payment', $inv, $p, $base, round($base * $rate / 100, 2), $rate);
                }
            }

            $this->refreshFixed($inv);
        });
    }

    /** Chèque encaissé : l'argent est entré, la commission « montant fixe » peut naître */
    public function onChequeCashed(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $inv = $this->lockInvoice($payment->invoice_id);
            $this->refreshFixed($inv);
        });
    }

    /** دفعة أُلغيت أو شيك رُفض */
    public function onPaymentCancelled(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $inv = $this->lockInvoice($payment->invoice_id);

            Commission::withoutGlobalScopes()
                ->where('kind', 'payment')
                ->where('payment_id', $payment->id)
                ->where('status', '!=', 'annulee')
                ->get()
                ->each(fn ($c) => $this->reverse($c, 'Paiement annulé (' . $payment->number . ')'));

            $this->refreshFixed($inv);
        });
    }

    /* ---------------- internes ---------------- */

    private function refreshFixed(Invoice $inv): void
    {
        $inv->refresh();

        $active = Commission::withoutGlobalScopes()
            ->where('kind', 'fixed')->where('invoice_id', $inv->id)
            ->where('status', '!=', 'annulee')->first();

        if ($inv->status === 'payee') {
            if ($active) {
                return;
            }
            $rule = CommissionRule::currentFor($inv->commercial_id);
            if ($rule && $rule->method === 'fixed_per_sale') {
                $this->create($rule, 'fixed', $inv, null, (float) $inv->total_ttc - (float) $inv->transport_amount - (float) $inv->installation_amount, round((float) $rule->value, 2));
            }
        } elseif ($active) {
            $this->reverse($active, 'Facture plus intégralement payée');
        }
    }

    private function reverse(Commission $commission, string $reason): void
    {
        $c = Commission::withoutGlobalScopes()->whereKey($commission->id)->lockForUpdate()->first();

        if (! $c || $c->kind === 'ajustement' || $c->status === 'annulee') {
            return;
        }

        if ($c->status === 'en_attente') {
            $c->update(['status' => 'annulee', 'cancelled_at' => now(), 'note' => $reason]);

            return;
        }

        // Déjà versée : ajustement négatif, déduit du prochain versement
        if (Commission::withoutGlobalScopes()->where('parent_id', $c->id)->exists()) {
            return;
        }

        Commission::withoutGlobalScopes()->create([
            'user_id'     => $c->user_id,
            'rule_id'     => $c->rule_id,
            'kind'        => 'ajustement',
            'parent_id'   => $c->id,
            'invoice_id'  => $c->invoice_id,
            'payment_id'  => $c->payment_id,
            'method'      => $c->method,
            'rate_value'  => $c->rate_value,
            'base_amount' => $c->base_amount,
            'amount'      => -1 * (float) $c->amount,
            'status'      => 'en_attente',
            'computed_at' => now(),
            'note'        => $reason,
        ]);
    }

    private function create(CommissionRule $rule, string $kind, Invoice $inv, ?Payment $payment, float $base, float $amount, ?float $rate = null): void
    {
        if ($amount <= 0) {
            return;
        }

        Commission::withoutGlobalScopes()->create([
            'user_id'     => $inv->commercial_id,
            'rule_id'     => $rule->id,
            'kind'        => $kind,
            'invoice_id'  => $inv->id,
            'payment_id'  => $payment?->id,
            'method'      => $rule->method,
            'rate_value'  => $rate ?? $rule->value,
            'base_amount' => round($base, 2),
            'amount'      => $amount,
            'status'      => 'en_attente',
            'computed_at' => now(),
        ]);
    }

    /**
     * Taux moyen de la facture pour la méthode « selon les produits » :
     * chaque ligne utilise le taux spécial de son produit, sinon le taux général.
     * Le taux de la facture = moyenne pondérée par le montant de chaque ligne (après remise),
     * donc un paiement partiel reçoit la même part de commission que la facture entière.
     */
    private function productRate(CommissionRule $rule, Invoice $inv): float
    {
        $special = $rule->productRates()->pluck('rate', 'product_id');
        $general = (float) $rule->value;
        $useHt   = config('commerce.commission_base') === 'ht';

        $sumBase = 0.0;
        $sumComm = 0.0;

        foreach ($inv->items()->get() as $item) {
            $ht   = (float) $item->line_total_ht;
            $base = $useHt ? $ht : $ht * (1 + (float) $item->tax_rate / 100);
            // Ligne libre (sans produit) : toujours le taux général du commercial
            $rate = $item->product_id
                ? (float) $special->get($item->product_id, $general)
                : $general;

            $sumBase += $base;
            $sumComm += $base * $rate;
        }

        return $sumBase > 0 ? round($sumComm / $sumBase, 4) : $general;
    }

    private function activeExists(string $kind, int $invoiceId, ?int $paymentId = null): bool
    {
        return Commission::withoutGlobalScopes()
            ->where('kind', $kind)->where('invoice_id', $invoiceId)
            ->when($paymentId, fn ($q) => $q->where('payment_id', $paymentId))
            ->where('status', '!=', 'annulee')
            ->exists();
    }

    private function base(float $amount, Invoice $inv): float
    {
        $total  = (float) $inv->total_ttc;
        $extras = (float) $inv->transport_amount + (float) $inv->installation_amount;

        if ($total <= 0) {
            return round($amount, 2);
        }

        // Transport et installation ne comptent jamais dans la base de commission :
        //  - base HT  : part des produits en HT (subtotal_ht ne contient pas ces montants)
        //  - base TTC : part des produits en TTC (total moins transport et installation)
        $ratio = config('commerce.commission_base') === 'ht'
            ? (float) $inv->subtotal_ht / $total
            : max(0.0, $total - $extras) / $total;

        return round($amount * $ratio, 2);
    }

    private function lockInvoice(int $id): Invoice
    {
        return Invoice::withoutGlobalScopes()->whereKey($id)->lockForUpdate()->firstOrFail();
    }
}
