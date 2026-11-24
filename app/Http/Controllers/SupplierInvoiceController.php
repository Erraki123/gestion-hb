<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierInvoiceController extends Controller
{
    public function index()
    {
        return redirect()->route('purchases.index', ['tab' => 'factures']);
    }

    public function create(Request $request)
    {
        $po = $request->filled('purchase_order_id')
            ? PurchaseOrder::with('items')->find($request->query('purchase_order_id'))
            : null;

        // Lignes de départ : la commande regroupée par taux de TVA
        $initialLines = $po
            ? $po->items->groupBy(fn ($i) => (float) $i->tax_rate)
                ->map(fn ($g, $rate) => ['tax_rate' => (float) $rate, 'base_ht' => round((float) $g->sum('line_total_ht'), 2)])
                ->values()->all()
            : [['tax_rate' => 20, 'base_ht' => '']];

        return view('supplier-invoices.create', [
            'suppliers'      => Supplier::where('is_active', true)->orderBy('name')->get(),
            'purchaseOrders' => PurchaseOrder::with('supplier')->where('status', '!=', 'annulee')->orderByDesc('id')->get(),
            'taxRates'       => Setting::taxRates(),
            'selectedPo'     => $po,
            'initialLines'   => $initialLines,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id'       => ['required', 'exists:suppliers,id'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'supplier_number'   => ['required', 'string', 'max:255'],
            'invoice_date'      => ['required', 'date'],
            'due_date'          => ['nullable', 'date'],
            'notes'             => ['nullable', 'string'],
            'lines'             => ['required', 'array'],
        ]);

        $invoice = DB::transaction(function () use ($data, $request) {
            $supplier = Supplier::findOrFail($data['supplier_id']);
            $dueDate = $data['due_date']
                ?? date('Y-m-d', strtotime($data['invoice_date'] . ' + ' . ($supplier->payment_days ?: 30) . ' days'));

            $lines = [];
            foreach ($request->input('lines', []) as $line) {
                $base = (float) ($line['base_ht'] ?? 0);
                $rate = (float) ($line['tax_rate'] ?? 0);
                if ($base <= 0) {
                    continue;
                }
                $lines[] = [
                    'tax_rate'   => $rate,
                    'base_ht'    => round($base, 2),
                    'tax_amount' => round($base * $rate / 100, 2),
                ];
            }

            if ($lines === []) {
                throw ValidationException::withMessages(['lines' => 'Saisissez au moins un montant HT.']);
            }

            $subtotal = round(array_sum(array_column($lines, 'base_ht')), 2);
            $tax      = round(array_sum(array_column($lines, 'tax_amount')), 2);

            $invoice = SupplierInvoice::create([
                'supplier_id'       => $data['supplier_id'],
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'supplier_number'   => $data['supplier_number'],
                'invoice_date'      => $data['invoice_date'],
                'due_date'          => $dueDate,
                'subtotal_ht'       => $subtotal,
                'tax_amount'        => $tax,
                'total_ttc'         => round($subtotal + $tax, 2),
                'amount_paid'       => 0,
                'status'            => 'non_payee',
                'notes'             => $data['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                $invoice->lines()->create($line);
            }

            return $invoice;
        });

        return redirect()->route('supplier-invoices.show', $invoice)
            ->with('success', 'Facture fournisseur enregistrée.');
    }

    public function show(SupplierInvoice $supplierInvoice)
    {
        $supplierInvoice->load(['supplier', 'purchaseOrder', 'lines', 'payments.user']);

        return view('supplier-invoices.show', compact('supplierInvoice'));
    }

    public function cancel(SupplierInvoice $supplierInvoice)
    {
        if ($supplierInvoice->payments()->where('status', 'valide')->exists()) {
            abort(403, 'Impossible d\'annuler une facture déjà payée.');
        }

        $supplierInvoice->status = 'annulee';
        $supplierInvoice->save();

        return redirect()->route('supplier-invoices.show', $supplierInvoice)
            ->with('success', 'Facture fournisseur annulée.');
    }
}
