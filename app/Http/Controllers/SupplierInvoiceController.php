<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceLine;
use App\Models\PurchaseOrder;
use App\Services\DocumentTotals;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierInvoiceController extends Controller
{
    public function index()
    {
        $invoices = SupplierInvoice::with('supplier')->latest('id')->paginate(15);

        return view('supplier-invoices.index', compact('invoices'));
    }

    public function create()
    {
        return view('supplier-invoices.create', [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'purchaseOrders' => PurchaseOrder::with('supplier')->where('status', '!=', 'annulee')->orderBy('id', 'desc')->get(),
            'taxRates' => Setting::taxRates(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'supplier_number' => ['required', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array'],
        ]);

        $invoice = DB::transaction(function () use ($data, $request) {
            $supplier = Supplier::findOrFail($data['supplier_id']);
            $invoiceDate = $data['invoice_date'];
            $dueDate = $data['due_date'] ?? date('Y-m-d', strtotime($invoiceDate . ' + ' . ($supplier->payment_days ?: 30) . ' days'));

            $lines = [];
            $totalBase = 0.0;
            foreach ($request->input('lines', []) as $line) {
                $base = (float) ($line['base_ht'] ?? 0);
                $rate = (float) ($line['tax_rate'] ?? 0);
                if ($base <= 0) {
                    continue;
                }
                $taxAmount = round($base * $rate / 100, 2);
                $lines[] = ['tax_rate' => $rate, 'base_ht' => round($base, 2), 'tax_amount' => $taxAmount];
                $totalBase += $base;
            }

            $subtotal = round($totalBase, 2);
            $tax = round(array_sum(array_map(fn ($l) => $l['tax_amount'], $lines)), 2);
            $total = round($subtotal + $tax, 2);

            $invoice = SupplierInvoice::create([
                'supplier_id' => $data['supplier_id'],
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'supplier_number' => $data['supplier_number'],
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'subtotal_ht' => $subtotal,
                'tax_amount' => $tax,
                'total_ttc' => $total,
                'amount_paid' => 0,
                'status' => 'non_payee',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                $invoice->lines()->create($line);
            }

            return $invoice;
        });

        return redirect()->route('supplier-invoices.show', $invoice)->with('success', 'Facture fournisseur enregistrée.');
    }

    public function show(SupplierInvoice $supplierInvoice)
    {
        $supplierInvoice->load(['supplier', 'lines', 'payments.user']);

        return view('supplier-invoices.show', compact('supplierInvoice'));
    }

    public function cancel(SupplierInvoice $supplierInvoice, Request $request)
    {
        if ($supplierInvoice->payments()->where('status', 'valide')->exists()) {
            abort(403, 'Impossible d\'annuler une facture déjà payée.');
        }

        $supplierInvoice->status = 'annulee';
        $supplierInvoice->save();

        return redirect()->route('supplier-invoices.show', $supplierInvoice)->with('success', 'Facture fournisseur annulée.');
    }
}
