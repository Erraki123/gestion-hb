<?php

namespace App\Http\Controllers;

use App\Events\InvoiceIssued;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\DocumentNumberService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

        $payments = $this->filtered($request)->paginate(15)->withQueryString();

        // Appel AJAX du filtre : on renvoie seulement les lignes et la pagination
        if ($request->expectsJson()) {
            return response()->json([
                'rows'  => view('payments._rows', ['payments' => $payments])->render(),
                'links' => (string) $payments->links(),
                'total' => $payments->total(),
            ]);
        }

        return view('payments.index', compact('payments', 'stats'));
    }

    /** Filtres : recherche (n°, client, facture, chèque, banque, référence, montant), mode, statut, période */
    private function filtered(Request $request)
    {
        $like = fn (string $v) => '%' . addcslashes($v, '\\%_') . '%';
        $str  = fn (string $key) => trim((string) $request->input($key, ''));

        $q      = $str('q');
        $method = $str('method');
        $status = $str('status');
        $from   = $str('from');
        $to     = $str('to');

        $isDate = fn (string $d) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;

        return Payment::with(['invoice', 'client', 'user'])
            ->when($q !== '', function ($query) use ($q, $like) {
                $pattern = $like($q);
                $amount  = str_replace([' ', ','], ['', '.'], $q);

                $query->where(function ($w) use ($pattern, $amount) {
                    $w->where('number', 'like', $pattern)
                        ->orWhere('reference', 'like', $pattern)
                        ->orWhere('cheque_number', 'like', $pattern)
                        ->orWhere('cheque_bank', 'like', $pattern)
                        ->orWhereHas('client', fn ($c) => $c->where('name', 'like', $pattern)->orWhere('company', 'like', $pattern))
                        ->orWhereHas('invoice', fn ($i) => $i->where('number', 'like', $pattern));

                    if (is_numeric($amount)) {
                        $w->orWhere('amount', (float) $amount);
                    }
                });
            })
            ->when(in_array($method, ['especes', 'virement', 'cheque'], true), fn ($query) => $query->where('method', $method))
            ->when(in_array($status, ['valide', 'annule'], true), fn ($query) => $query->where('status', $status))
            ->when($status === 'cheque_en_attente', fn ($query) => $query->where('status', 'valide')->where('cheque_status', 'en_attente'))
            ->when($isDate($from), fn ($query) => $query->whereDate('paid_at', '>=', $from))
            ->when($isDate($to), fn ($query) => $query->whereDate('paid_at', '<=', $to))
            ->latest('id');
    }

    /**
     * Enregistrement « tout en un » depuis la page de la facture :
     *   1. brouillon  -> conditions / notes / échéance, puis émission (numéro définitif)
     *   2. facture émise -> échéance modifiée si besoin
     *   3. paiement enregistré si un montant est saisi
     * Le tout dans UNE transaction : si le paiement échoue, rien n'est émis.
     */
    public function store(Request $request, Invoice $invoice, PaymentService $service, InvoiceService $invoices)
    {
        abort_unless(
            $invoice->type === 'facture'
                && in_array($invoice->status, ['brouillon', 'emise', 'partiellement_payee'], true),
            403,
            'Cette facture n\'accepte pas de modification ou de paiement.'
        );

        $data = $request->validate([
            'amount'          => ['nullable', 'numeric', 'min:0'],
            'method'          => ['required_with:amount', 'nullable', 'in:especes,virement,cheque'],
            'paid_at'         => ['nullable', 'date', 'before_or_equal:today'],
            'due_date'        => ['nullable', 'date', 'after_or_equal:today'],
            'reference'       => ['nullable', 'string', 'max:255'],
            'cheque_number'   => ['required_if:method,cheque', 'nullable', 'string', 'max:50'],
            'cheque_bank'     => ['nullable', 'string', 'max:100'],
            'cheque_due_date' => ['required_if:method,cheque', 'nullable', 'date'],
            'notes'           => ['nullable', 'string', 'max:2000'],
            'cheque_scan'     => \App\Services\PaymentDocumentService::RULE,
            'other_file'      => \App\Services\PaymentDocumentService::RULE,
            // champs de la facture elle-même
            'days'            => ['nullable', 'integer', 'in:0,15,30,60'],
            'invoice_terms'   => ['nullable', 'string', 'max:3000'],
            'invoice_notes'   => ['nullable', 'string', 'max:3000'],
        ]);

        $amount     = (float) ($data['amount'] ?? 0);
        $wasDraft   = $invoice->status === 'brouillon';
        $hasDays    = isset($data['days']);
        $messages   = [];
        $issuedCopy = null;
        $payment    = null;

        if (! $wasDraft && $amount <= 0 && ! $hasDays) {
            return back();
        }

        DB::transaction(function () use ($invoice, $data, $amount, $wasDraft, $hasDays, $service, $invoices, &$messages, &$issuedCopy, &$payment) {
            $i = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($wasDraft) {
                if ($i->status !== 'brouillon') {
                    throw ValidationException::withMessages(['status' => 'Cette facture est déjà émise.']);
                }

                $i->update([
                    'terms'    => $data['invoice_terms'] ?? $i->terms,
                    'notes'    => $data['invoice_notes'] ?? $i->notes,
                    'due_date' => $hasDays
                        ? today()->addDays((int) $data['days'])
                        : (! empty($data['due_date']) ? $data['due_date'] : $i->due_date),
                ]);

                // Même règle d'émission partout : numéro, verrouillage, sortie de stock
                $issuedCopy = $invoices->issueLocked($i->id, auth()->id());
                $messages[] = 'Facture ' . $issuedCopy->number . ' émise.';
            } elseif ($hasDays) {
                $i->update(['due_date' => $i->issue_date->copy()->addDays((int) $data['days'])]);
                $messages[] = 'Échéance mise à jour.';
            }

            if ($amount > 0) {
                $payment = $service->record($issuedCopy ?? $i->fresh(), array_merge($data, [
                    'amount'  => $amount,
                    'paid_at' => $data['paid_at'] ?? today()->toDateString(),
                ]), auth()->id());
                $messages[] = 'Paiement ' . $payment->number . ' enregistré.';
            }
        });

        if ($issuedCopy) {
            InvoiceIssued::dispatch($issuedCopy);
        }

        $redirect = redirect()->route('invoices.show', $invoice)
            ->with('success', implode(' ', $messages) ?: 'Enregistré.');

        if ($issuedCopy || $payment) {
            $redirect->with('invoice_pdf', route('invoices.pdf', $invoice));
        }

        if ($payment) {
            $redirect->with('receipt_pdf', route('payments.pdf', $payment));

            $fileError = app(\App\Services\PaymentDocumentService::class)->attachFromRequest($payment, $request, auth()->id());
            if ($fileError) {
                $redirect->withErrors(['file' => $fileError]);
            }
        }

        return $redirect;
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

        return redirect()->route('admin.pdf-studio.show', ['type' => 'receipt', 'id' => $payment->id, 'download' => 1]); // HNB_ALL_TYPES_V34
    }
}