<?php

namespace App\Http\Controllers;

use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Services\SupplierPaymentService;
use Illuminate\Http\Request;

class SupplierPaymentController extends Controller
{
    public function store(Request $request, SupplierInvoice $supplierInvoice, SupplierPaymentService $service)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:especes,virement,cheque'],
            'reference' => ['nullable', 'string', 'max:255'],
            'cheque_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $payment = $service->record(
            $supplierInvoice,
            (float) $data['amount'],
            $data['method'],
            $data['reference'] ?? null,
            $data['cheque_number'] ?? null,
            $data['notes'] ?? null,
        );

        return redirect()->route('supplier-invoices.show', $supplierInvoice)->with('success', 'Paiement enregistré.');
    }

    public function cancel(SupplierPayment $supplierPayment, Request $request, SupplierPaymentService $service)
    {
        $request->validate(['reason' => ['required', 'string', 'min:3']]);
        $service->cancel($supplierPayment, $request->input('reason'));

        return redirect()->route('supplier-invoices.show', $supplierPayment->supplier_invoice_id)->with('success', 'Paiement annulé.');
    }
}
