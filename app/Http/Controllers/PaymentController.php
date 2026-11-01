<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $validBase = Payment::where('status', 'valide');

        $stats = [
            'month'   => (clone $validBase)->whereYear('paid_at', now()->year)->whereMonth('paid_at', now()->month)->sum('amount'),
            'today'   => (clone $validBase)->whereDate('paid_at', today())->sum('amount'),
            'cheques' => (clone $validBase)->where('cheque_status', 'en_attente')->sum('amount'),
            'cheques_count' => (clone $validBase)->where('cheque_status', 'en_attente')->count(),
        ];

        $payments = Payment::with(['invoice', 'client', 'user'])
            ->when($request->q, fn ($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('number', 'like', "%{$s}%")
                  ->orWhere('reference', 'like', "%{$s}%")
                  ->orWhere('cheque_number', 'like', "%{$s}%")
                  ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$s}%"))
                  ->orWhereHas('invoice', fn ($i) => $i->where('number', 'like', "%{$s}%"));
            }))
            ->when($request->method, fn ($q, $m) => $q->where('method', $m))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->cheque_status, fn ($q, $s) => $q->where('cheque_status', $s))
            ->when($request->from, fn ($q, $d) => $q->whereDate('paid_at', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('paid_at', '<=', $d))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('payments.index', compact('payments', 'stats'));
    }

    public function store(Request $request, Invoice $invoice, PaymentService $service)
    {
        $data = $request->validate([
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'method'          => ['required', 'in:especes,virement,cheque'],
            'paid_at'         => ['required', 'date', 'before_or_equal:today'],
            'reference'       => ['nullable', 'string', 'max:255'],
            'cheque_number'   => ['required_if:method,cheque', 'nullable', 'string', 'max:50'],
            'cheque_bank'     => ['nullable', 'string', 'max:100'],
            'cheque_due_date' => ['nullable', 'date'],
            'notes'           => ['nullable', 'string', 'max:2000'],
        ]);

        $payment = $service->record($invoice, $data, auth()->id());

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Paiement ' . $payment->number . ' enregistré.');
    }

    public function cancel(Request $request, Payment $payment, PaymentService $service)
    {
        $data = $request->validate(['cancel_reason' => ['required', 'string', 'max:255']]);

        $service->cancel($payment, auth()->id(), $data['cancel_reason']);

        return back()->with('success', 'Paiement annulé, le solde a été recalculé.');
    }

    public function cheque(Request $request, Payment $payment, PaymentService $service)
    {
        $to = $request->validate(['to' => ['required', 'in:encaisse,rejete']])['to'];

        $service->updateCheque($payment, $to, auth()->id());

        return back()->with('success', $to === 'encaisse'
            ? 'Chèque marqué comme encaissé.'
            : 'Chèque rejeté : le paiement a été annulé.');
    }

    /** Reçu de paiement (PDF) */
    public function pdf(Payment $payment)
    {
        $payment->load(['client', 'invoice', 'user']);

        return Pdf::loadView('payments.receipt', [
            'payment' => $payment,
            'words'   => Money::words((float) $payment->amount),
        ])->setPaper('a4')->stream($payment->number . '.pdf');
    }
}