<?php

namespace App\Http\Controllers;

use App\Events\CreditNoteIssued;
use App\Models\CustomerReturn;
use App\Models\CustomerReturnItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Services\DocumentNumberService;
use App\Services\DocumentTotals;
use App\Services\PaymentService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $returns = CustomerReturn::with(['invoice', 'client', 'commercial'])
            ->when($request->q, fn ($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('number', 'like', "%{$s}%")
                  ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('returns.index', compact('returns'));
    }

    public function store(Request $request, Invoice $invoice)
    {
        abort_unless(in_array($invoice->type, ['facture'], true) && in_array($invoice->status, ['emise', 'partiellement_payee', 'payee'], true), 403, 'Retour impossible pour ce document.');

        $invoice->load('items');

        $hasReturnable = $invoice->items->contains(fn ($item) => (float) $item->returnableQuantity() > 0);

        if (! $hasReturnable) {
            throw ValidationException::withMessages(['invoice' => 'Aucune quantité retournable sur cette facture.']);
        }

        $draft = DB::transaction(function () use ($invoice) {
            $return = CustomerReturn::create([
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'commercial_id' => $invoice->commercial_id,
                'status' => 'brouillon',
                'return_date' => now()->toDateString(),
                'reason' => 'autre',
                'notes' => null,
            ]);

            foreach ($invoice->items as $item) {
                $returnable = (float) $item->returnableQuantity();
                if ($returnable <= 0) {
                    continue;
                }

                $return->items()->create([
                    'invoice_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'unit' => $item->unit,
                    'quantity' => 0,
                    'unit_price' => (float) $item->unit_price,
                    'discount_percent' => (float) $item->discount_percent,
                    'tax_rate' => (float) $item->tax_rate,
                    'line_total_ht' => 0,
                    'restock' => true,
                ]);
            }

            return $return;
        });

        return redirect()->route('returns.show', $draft)->with('success', 'Retour client créé en brouillon.');
    }

    public function show(CustomerReturn $customerReturn)
    {
        $customerReturn->load(['invoice', 'client', 'commercial', 'items.invoiceItem', 'items.product']);

        return view('returns.show', compact('customerReturn'));
    }

    public function update(Request $request, CustomerReturn $customerReturn)
    {
        abort_unless($customerReturn->status === 'brouillon', 403, 'Seule une demande de retour en brouillon peut être mise à jour.');

        $data = $request->validate([
            'return_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'in:defectueux,erreur,surplus,autre'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $customerReturn->fill([
            'return_date' => $data['return_date'] ?? $customerReturn->return_date,
            'reason' => $data['reason'] ?? $customerReturn->reason,
            'notes' => $data['notes'] ?? $customerReturn->notes,
        ]);
        $customerReturn->save();

        foreach ($customerReturn->items as $item) {
            $max = (float) ($item->invoiceItem?->returnableQuantity() ?? 0);
            $qty = (float) ($request->input('quantity.' . $item->id, 0) ?? 0);
            $restock = (bool) $request->input('restock.' . $item->id, false);

            if ($qty <= 0) {
                $item->update(['quantity' => 0, 'restock' => $restock, 'line_total_ht' => 0]);
                continue;
            }

            if ($qty > $max + 0.0001) {
                throw ValidationException::withMessages(['quantity' => 'La quantité retournée ne peut pas dépasser le restant retournable.']);
            }

            $lineTotal = round($qty * (float) $item->unit_price * (1 - ((float) $item->discount_percent / 100)), 2);
            $item->update([
                'quantity' => round($qty, 2),
                'restock' => $restock,
                'line_total_ht' => $lineTotal,
            ]);
        }

        return redirect()->route('returns.show', $customerReturn)->with('success', 'Retour mis à jour.');
    }

    public function destroy(CustomerReturn $customerReturn)
    {
        abort_unless($customerReturn->status === 'brouillon', 403, 'Seule une demande de retour en brouillon peut être supprimée.');

        $customerReturn->delete();

        return redirect()->route('returns.index')->with('success', 'Retour supprimé.');
    }

    public function validate(CustomerReturn $customerReturn, DocumentNumberService $numbers, StockService $stock, PaymentService $paymentService)
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Seule l’admin peut valider un retour.');

        $data = DB::transaction(function () use ($customerReturn, $numbers, $stock, $paymentService) {
            $return = CustomerReturn::withoutGlobalScopes()->whereKey($customerReturn->id)->lockForUpdate()->firstOrFail();
            $invoice = Invoice::withoutGlobalScopes()->whereKey($return->invoice_id)->lockForUpdate()->firstOrFail();

            if ($return->status !== 'brouillon') {
                throw ValidationException::withMessages(['return' => 'Seul un retour brouillon peut être validé.']);
            }

            $lines = [];
            foreach ($return->items()->with('invoiceItem')->get() as $item) {
                $qty = (float) $item->quantity;
                if ($qty <= 0) {
                    continue;
                }

                $max = (float) ($item->invoiceItem?->returnableQuantity() ?? 0);
                if ($qty > $max + 0.0001) {
                    throw ValidationException::withMessages(['quantity' => 'Quantité supérieure au restant retournable.']);
                }

                $lines[] = [
                    'quantity' => $qty,
                    'unit_price' => (float) $item->unit_price,
                    'discount_percent' => (float) $item->discount_percent,
                    'tax_rate' => (float) $item->tax_rate,
                ];
            }

            if (empty($lines)) {
                throw ValidationException::withMessages(['return' => 'Ajoutez au moins une ligne retournée.']);
            }

            $totals = app(DocumentTotals::class)->compute($lines);
            $rtNumber = $numbers->next('RT');

            $creditNote = Invoice::create([
                'number' => $numbers->next('AV'),
                'type' => 'avoir',
                'parent_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'commercial_id' => $invoice->commercial_id,
                'status' => 'emise',
                'issue_date' => today(),
                'due_date' => today()->addDays(config('commerce.payment_days', 30)),
                'subtotal_ht' => $totals['subtotal_ht'],
                'tax_rate' => $totals['tax_rate'],
                'tax_amount' => $totals['tax_amount'],
                'total_ttc' => $totals['total_ttc'],
                'notes' => 'Retour ' . $rtNumber . ' sur facture ' . $invoice->number,
            ]);

            foreach ($return->items()->with('invoiceItem')->get() as $item) {
                $qty = (float) $item->quantity;
                if ($qty <= 0) {
                    continue;
                }

                $creditNote->items()->create([
                    'product_id' => $item->product_id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'unit' => $item->unit,
                    'quantity' => $qty,
                    'unit_price' => (float) $item->unit_price,
                    'discount_percent' => (float) $item->discount_percent,
                    'tax_rate' => (float) $item->tax_rate,
                    'line_total_ht' => round($qty * (float) $item->unit_price * (1 - ((float) $item->discount_percent / 100)), 2),
                ]);
            }

            $return->update([
                'number' => $rtNumber,
                'status' => 'valide',
                'return_date' => $return->return_date ?? today(),
                'validated_at' => now(),
                'validated_by' => auth()->id(),
                'credit_note_id' => $creditNote->id,
            ]);

            foreach ($return->items()->with('invoiceItem')->get() as $item) {
                if (! $item->restock || (float) $item->quantity <= 0) {
                    continue;
                }

                $product = $item->product;
                if ($product) {
                    $stock->move($product, 'entree', (float) $item->quantity, auth()->id(), 'Retour client', $rtNumber);
                }
            }

            $paymentService->refreshInvoiceById($invoice->id);

            return ['return' => $return->fresh(['items', 'creditNote']), 'credit' => $creditNote->fresh('items')];
        });

        event(new CreditNoteIssued($data['credit']));

        return redirect()->route('returns.show', $data['return'])->with('success', 'Retour validé et avoir créé.');
    }
}
