<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PaymentExportController extends Controller
{
    /** PDF global des paiements valides, avec les mêmes filtres que la page Paiements. */
    public function pdf(Request $request)
    {
        $f = $request->validate([
            'q'             => ['nullable', 'string', 'max:100'],
            'from'          => ['nullable', 'date'],
            'to'            => ['nullable', 'date', 'after_or_equal:from'],
            'method'        => ['nullable', 'in:especes,virement,cheque'],
            'cheque_status' => ['nullable', 'in:en_attente,encaisse,rejete'],
            'invoice_id'    => ['nullable', 'integer'],
        ]);

        $from = $f['from'] ?? now()->startOfMonth()->toDateString();
        $to   = $f['to'] ?? now()->toDateString();

        // Le Global Scope « owner » limite déjà un commercial à ses propres paiements
        $payments = Payment::where('status', 'valide')
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->when($f['q'] ?? null, fn ($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('number', 'like', "%{$s}%")
                  ->orWhere('reference', 'like', "%{$s}%")
                  ->orWhere('cheque_number', 'like', "%{$s}%")
                  ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$s}%"))
                  ->orWhereHas('invoice', fn ($i) => $i->where('number', 'like', "%{$s}%"));
            }))
            ->when($f['method'] ?? null, fn ($q, $m) => $q->where('method', $m))
            ->when($f['cheque_status'] ?? null, fn ($q, $s) => $q->where('cheque_status', $s))
            ->when($f['invoice_id'] ?? null, fn ($q, $id) => $q->where('invoice_id', $id))
            ->orderBy('paid_at')->orderBy('id')
            ->get();

        $invoices = Invoice::withoutGlobalScopes()->withTrashed()
            ->whereIn('id', $payments->pluck('invoice_id'))->pluck('number', 'id');
        $clients = Client::withoutGlobalScopes()->withTrashed()
            ->whereIn('id', $payments->pluck('client_id'))->pluck('name', 'id');

        $byMethod = $payments->groupBy('method')->map(fn ($g) => round((float) $g->sum('amount'), 2));
        $total    = round((float) $payments->sum('amount'), 2);

        return Pdf::loadView('payments.export-pdf', compact('payments', 'invoices', 'clients', 'byMethod', 'total', 'from', 'to'))
            ->setPaper('a4', 'landscape')
            ->stream('paiements-' . $from . '_' . $to . '.pdf');
    }
}