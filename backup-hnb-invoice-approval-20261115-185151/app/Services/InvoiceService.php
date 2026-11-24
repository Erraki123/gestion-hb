<?php

namespace App\Services;

use App\Events\InvoiceIssued;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(private DocumentNumberService $numbers, private StockService $stock) {}

    /** Émission complète : transaction, puis événement (commissions) après validation. */
    public function issue(Invoice $invoice, int $userId): Invoice
    {
        $issued = DB::transaction(fn () => $this->issueLocked($invoice->id, $userId));

        InvoiceIssued::dispatch($issued);

        return $issued;
    }

    /** À appeler À L'INTÉRIEUR d'une transaction. N'envoie pas l'événement. */
    public function issueLocked(int $invoiceId, int $userId): Invoice
    {
        $i = Invoice::whereKey($invoiceId)->lockForUpdate()->firstOrFail();

        if ($i->type !== 'facture' || $i->status !== 'brouillon') {
            throw ValidationException::withMessages(['status' => 'Cette facture est déjà émise.']);
        }

        $due = $i->due_date && $i->due_date->gte(today())
            ? $i->due_date
            : today()->addDays((int) config('commerce.payment_days'));

        $i->update([
            'number'     => $this->numbers->next('FA'),
            'status'     => 'emise',
            'issue_date' => today(),
            'due_date'   => $due,
        ]);

        // Facture directe (sans commande) : le stock sort à l'émission
        if ($i->order_id === null && Setting::get('direct_invoice_stock', 'yes') !== 'no') {
            $this->deductStock($i, $userId);
        }

        return $i->fresh();
    }

    /** Avoir complet : le stock sorti à l'émission revient. */
    public function restoreStock(Invoice $invoice, string $reference, int $userId): void
    {
        if (! $invoice->stock_moved) {
            return;
        }

        $invoice->loadMissing('items');

        foreach ($invoice->items as $item) {
            $product = $item->product_id ? Product::find($item->product_id) : null;

            if ($product) {
                $this->stock->move($product, 'entree', (float) $item->quantity, $userId, 'Avoir ' . $reference, $reference);
            }
        }

        $invoice->update(['stock_moved' => false]);
    }

    private function deductStock(Invoice $invoice, int $userId): void
    {
        $invoice->load('items');

        foreach ($invoice->items as $item) {
            $product = $item->product_id ? Product::find($item->product_id) : null;

            if (! $product) {
                continue;
            }

            try {
                $this->stock->move($product, 'sortie', (float) $item->quantity, $userId, 'Facture émise', $invoice->number);
            } catch (ValidationException $e) {
                throw ValidationException::withMessages([
                    'items' => $item->name . ' : ' . collect($e->errors())->flatten()->first(),
                ]);
            }
        }

        $invoice->update(['stock_moved' => true]);
    }
}
