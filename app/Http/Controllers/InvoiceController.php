<?php

namespace App\Http\Controllers;

use App\Events\InvoiceCancelled;
use App\Events\InvoiceIssued;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\DocumentNumberService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        // Indicateurs (factures émises, hors avoirs et annulées)
        $base = Invoice::where('type', 'facture')->whereNotIn('status', ['brouillon', 'annulee']);

        $stats = [
            'billed' => (clone $base)->sum('total_ttc'),
            'paid'   => (clone $base)->sum('amount_paid'),
            'late'   => (clone $base)->overdue()->sum(DB::raw('total_ttc - amount_paid')),
        ];
        $stats['remaining'] = $stats['billed'] - $stats['paid'];

        $invoices = Invoice::with(['client', 'commercial'])
            ->when($request->q, fn ($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('number', 'like', "%{$s}%")
                  ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status === 'en_retard', fn ($q) => $q->overdue())
            ->when($request->status && $request->status !== 'en_retard',
                fn ($q) => $q->where('status', $request->status))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('invoices.index', compact('invoices', 'stats'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'commercial', 'items', 'order', 'parent', 'credit', 'payments.user']);

        return view('invoices.show', compact('invoice'));
    }

    /** Commande confirmée ou terminée -> facture brouillon (1 clic) */
    public function fromOrder(Order $order)
    {
        $invoice = DB::transaction(function () use ($order) {
            $o = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($o->status, ['confirmee', 'terminee'], true)) {
                throw ValidationException::withMessages(['status' => 'La commande doit être confirmée avant la facturation.']);
            }

            $exists = Invoice::withTrashed()
                ->where('order_id', $o->id)
                ->where('type', 'facture')
                ->where('status', '!=', 'annulee')
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages(['status' => 'Cette commande a déjà une facture.']);
            }

            $o->load('items');

            $invoice = Invoice::create([
                'type'          => 'facture',
                'order_id'      => $o->id,
                'client_id'     => $o->client_id,
                'commercial_id' => $o->commercial_id,
                'status'        => 'brouillon',
                'issue_date'    => today(),
                'due_date'      => today()->addDays(config('commerce.payment_days')),
                'subtotal_ht'   => $o->subtotal_ht,
                'tax_rate'      => $o->tax_rate,
                'tax_amount'    => $o->tax_amount,
                'total_ttc'     => $o->total_ttc,
                'notes'         => $o->notes,
            ]);

            $invoice->items()->createMany(
                $o->items->map(fn ($i) => $i->only([
                    'product_id', 'name', 'sku', 'unit', 'quantity',
                    'unit_price', 'discount_percent', 'line_total_ht',
                ]))->all()
            );

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Facture brouillon créée. Vérifiez puis émettez-la.');
    }

    /** Seuls échéance, conditions et notes sont modifiables (en brouillon) */
    public function update(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->isDraft(), 403, 'Une facture émise ne peut plus être modifiée.');

        $data = $request->validate([
            'due_date' => ['required', 'date', 'after_or_equal:' . $invoice->issue_date->format('Y-m-d')],
            'terms'    => ['nullable', 'string', 'max:3000'],
            'notes'    => ['nullable', 'string', 'max:3000'],
        ]);

        $invoice->update($data);

        return back()->with('success', 'Facture mise à jour.');
    }

    public function destroy(Invoice $invoice)
    {
        abort_unless($invoice->isDraft(), 403, 'Seul un brouillon peut être supprimé.');

        $invoice->delete(); // pas de numéro attribué => aucune fracture dans la numérotation

        return redirect()->route('invoices.index')->with('success', 'Brouillon supprimé.');
    }

    /** Émission : numéro définitif + verrouillage */
    public function issue(Invoice $invoice, DocumentNumberService $numbers)
    {
        DB::transaction(function () use ($invoice, $numbers) {
            $i = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($i->status !== 'brouillon') {
                throw ValidationException::withMessages(['status' => 'Cette facture est déjà émise.']);
            }

            $due = $i->due_date && $i->due_date->gte(today())
                ? $i->due_date
                : today()->addDays(config('commerce.payment_days'));

            $i->update([
                'number'     => $numbers->next('FA'),
                'status'     => 'emise',
                'issue_date' => today(),
                'due_date'   => $due,
            ]);
        });

        InvoiceIssued::dispatch($invoice->fresh());

        return back()->with('success', 'Facture émise.');
    }

    /** Annulation = création d'un avoir complet (jamais de suppression) */
    public function credit(Invoice $invoice, DocumentNumberService $numbers)
    {
        $credit = DB::transaction(function () use ($invoice, $numbers) {
            $i = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($i->type !== 'facture' || $i->status !== 'emise') {
                throw ValidationException::withMessages(['status' => 'Seule une facture émise peut être annulée par avoir.']);
            }

            if ((float) $i->amount_paid > 0) {
                throw ValidationException::withMessages(['status' => 'Cette facture a des paiements : traitez d\'abord le remboursement (étape Paiements).']);
            }

            if (Invoice::where('parent_id', $i->id)->exists()) {
                throw ValidationException::withMessages(['status' => 'Un avoir existe déjà pour cette facture.']);
            }

            $i->load('items');

            $credit = Invoice::create([
                'number'        => $numbers->next('AV'),
                'type'          => 'avoir',
                'parent_id'     => $i->id,
                'client_id'     => $i->client_id,
                'commercial_id' => $i->commercial_id,
                'status'        => 'emise',
                'issue_date'    => today(),
                'subtotal_ht'   => $i->subtotal_ht,
                'tax_rate'      => $i->tax_rate,
                'tax_amount'    => $i->tax_amount,
                'total_ttc'     => $i->total_ttc,
                'notes'         => 'Avoir annulant la facture ' . $i->number,
            ]);

            $credit->items()->createMany(
                $i->items->map(fn ($it) => $it->only([
                    'product_id', 'name', 'sku', 'unit', 'quantity',
                    'unit_price', 'discount_percent', 'line_total_ht',
                ]))->all()
            );

            $i->update(['status' => 'annulee']);

            return $credit;
        });

        InvoiceCancelled::dispatch($invoice->fresh());

        return redirect()->route('invoices.show', $credit)->with('success', 'Avoir créé, facture annulée.');
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load(['client', 'commercial', 'items', 'parent']);

        return Pdf::loadView('invoices.pdf', compact('invoice'))
            ->setPaper('a4')
            ->stream(($invoice->number ?? 'brouillon-' . $invoice->id) . '.pdf');
    }
}