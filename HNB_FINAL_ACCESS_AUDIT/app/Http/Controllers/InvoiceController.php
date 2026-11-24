<?php

namespace App\Http\Controllers;

use App\Events\InvoiceCancelled;
use App\Events\InvoiceIssued;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\DocumentLines;
use App\Services\DocumentNumberService;
use App\Services\InvoiceService;
use App\Services\PaymentDocumentService;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        // Indicateurs (factures émises, hors avoirs et annulées)
        $base = Invoice::where('type', 'facture')->whereNotIn('status', ['brouillon', 'annulee']);

        $stats = [
            'billed'  => (clone $base)->sum('total_ttc'),
            'paid'    => (clone $base)->sum('amount_paid'),
            'cashed'  => (clone $base)->sum('amount_cashed'),
            'cheques' => (clone $base)->sum('amount_cheque_pending'),
            'late'    => (clone $base)->overdue()->sum(DB::raw('total_ttc - amount_paid')),
        ];
        $stats['remaining'] = $stats['billed'] - $stats['paid'];

        $tab = $this->tabFrom($request);

        $counts = [
            'a_encaisser' => Invoice::where('type', 'facture')->whereIn('status', Invoice::OPEN)->count(),
            'en_retard'   => Invoice::overdue()->count(),
            'payees'      => Invoice::where('type', 'facture')->whereIn('status', Invoice::COVERED)->count(),
            'avoirs'      => Invoice::where('type', 'avoir')->count(),
            'brouillons'  => Invoice::where('status', 'brouillon')->count(),
        ];

        $invoices = $this->filtered($request, $tab)->paginate(15)->withQueryString();

        // Appel AJAX du filtre : on renvoie seulement les lignes et la pagination
        if ($request->expectsJson()) {
            return response()->json([
                'rows'  => view('invoices._rows', ['invoices' => $invoices])->render(),
                'links' => (string) $invoices->links(),
                'total' => $invoices->total(),
            ]);
        }

        $commerciaux = auth()->user()->isAdmin()
            ? \App\Models\User::where('role', 'commercial')->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('invoices.index', compact('invoices', 'stats', 'tab', 'counts', 'commerciaux'));
    }

    private function tabFrom(Request $request): string
    {
        // l'ancien lien ?status=en_retard du tableau de bord reste valable
        $tab = (string) $request->query('tab', '');

        if ($tab === '' && $request->query('status') === 'en_retard') {
            return 'en_retard';
        }

        return in_array($tab, ['a_encaisser', 'en_retard', 'payees', 'avoirs', 'brouillons'], true) ? $tab : 'tous';
    }

    /** Recherche complète : numéro, client, n° de chèque, banque, référence, n° de paiement, montant, dates… */
    private function filtered(Request $request, string $tab)
    {
        $like = fn (string $v) => '%' . addcslashes($v, '\\%_') . '%';
        $str  = fn (string $key) => trim((string) $request->input($key, ''));

        $q       = $str('q');
        $number  = $str('number');
        $client  = $str('client');
        $cheque  = $str('cheque');
        $method  = $str('method');
        $chqStat = $str('cheque_status');
        $status  = $str('status');
        $due     = $str('due');
        $from    = $str('from');
        $to      = $str('to');

        $isDate  = fn (string $d) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;
        $open    = Invoice::OPEN;

        return Invoice::with(['client', 'commercial', 'items'])
            // Recherche globale
            ->when($q !== '', function ($query) use ($q, $like) {
                $pattern = $like($q);
                $amount  = str_replace([' ', ','], ['', '.'], $q);

                $query->where(function ($w) use ($pattern, $amount) {
                    $w->where('number', 'like', $pattern)
                        ->orWhereHas('client', fn ($c) => $c->where('name', 'like', $pattern)
                            ->orWhere('company', 'like', $pattern)->orWhere('phone', 'like', $pattern))
                        ->orWhereIn('id', Payment::select('invoice_id')->where(fn ($p) => $p
                            ->where('number', 'like', $pattern)->orWhere('reference', 'like', $pattern)
                            ->orWhere('cheque_number', 'like', $pattern)->orWhere('cheque_bank', 'like', $pattern)));

                    if (is_numeric($amount)) {
                        $w->orWhere('total_ttc', (float) $amount);
                    }
                });
            })
            // Filtres par colonne
            ->when($number !== '', fn ($query) => $query->where('number', 'like', $like($number)))
            ->when($client !== '', fn ($query) => $query->whereHas('client', fn ($c) => $c
                ->where('name', 'like', $like($client))->orWhere('company', 'like', $like($client))))
            ->when($isDate($due), fn ($query) => $query->whereDate('due_date', $due))
            ->when($isDate($from), fn ($query) => $query->whereDate('issue_date', '>=', $from))
            ->when($isDate($to), fn ($query) => $query->whereDate('issue_date', '<=', $to))
            ->when(in_array($status, ['emise', 'partiellement_payee', 'cheque_en_attente', 'payee', 'annulee'], true),
                fn ($query) => $query->where('type', 'facture')->where('status', $status))
            ->when($status === 'en_retard', fn ($query) => $query->overdue())
            ->when($status === 'avoir', fn ($query) => $query->where('type', 'avoir'))
            // Chèque / mode de paiement : une seule sous-requête pour que les critères portent sur le MÊME paiement
            ->when($cheque !== '' || in_array($method, ['especes', 'virement', 'cheque'], true)
                || in_array($chqStat, ['en_attente', 'encaisse', 'rejete'], true),
                function ($query) use ($cheque, $method, $chqStat, $like) {
                    $pay = Payment::select('invoice_id');

                    if ($cheque !== '') {
                        $pay->where(fn ($p) => $p->where('cheque_number', 'like', $like($cheque))
                            ->orWhere('cheque_bank', 'like', $like($cheque)));
                    }
                    if (in_array($method, ['especes', 'virement', 'cheque'], true)) {
                        $pay->where('method', $method);
                    }
                    if (in_array($chqStat, ['en_attente', 'encaisse', 'rejete'], true)) {
                        $pay->where('cheque_status', $chqStat);
                    }

                    $query->whereIn('id', $pay);
                })
            ->when(auth()->user()->isAdmin() && $request->filled('commercial_id'),
                fn ($query) => $query->where('commercial_id', (int) $request->input('commercial_id')))
            // Onglets
            ->when($tab === 'a_encaisser', fn ($query) => $query->where('type', 'facture')->whereIn('status', $open))
            ->when($tab === 'en_retard', fn ($query) => $query->overdue())
            ->when($tab === 'payees', fn ($query) => $query->where('type', 'facture')->whereIn('status', Invoice::COVERED))
            ->when($tab === 'avoirs', fn ($query) => $query->where('type', 'avoir'))
            ->when($tab === 'brouillons', fn ($query) => $query->where('status', 'brouillon'))
            ->when(in_array($tab, ['a_encaisser', 'en_retard'], true),
                fn ($query) => $query->orderBy('due_date')->orderBy('id'),
                fn ($query) => $query->latest('id'));
    }

    public function create(Request $request)
    {
        $days = (int) config('commerce.payment_days');

        return view('invoices.create', [
            'mode'           => Setting::priceMode(),
            'rates'          => Setting::taxRates(),
            'defaultRate'    => (float) config('commerce.tax_rate'),
            'defaultDays'    => in_array($days, [0, 15, 30, 60], true) ? $days : 30,
            'selectedClient' => $request->query('client_id'),
        ]);
    }

    /**
     * « Enregistrer » = la facture est créée ET émise (numéro FA, sortie de stock) en une seule opération.
     * Plus de brouillon : si le stock manque, rien n'est créé et aucun numéro n'est perdu.
     */
    public function store(Request $request, DocumentLines $lines, InvoiceService $service, PaymentService $payments)
    {
        $data = $request->validate([
            'action'                   => ['required', 'in:issue,issue_pay'],
            'client_id'                => ['required', 'integer'],
            'terms_days'               => ['nullable', 'in:0,15,30,60'],
            'due_date'                 => ['nullable', 'date', 'after_or_equal:today'],
            'notes'                    => ['nullable', 'string', 'max:3000'],
            'items'                    => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id'       => ['nullable', 'integer', 'exists:products,id'],
            'items.*.name'             => ['required_without:items.*.product_id', 'nullable', 'string', 'max:255'],
            'items.*.unit'             => ['nullable', 'string', 'max:20'],
            'items.*.quantity'         => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'       => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate'         => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'amount'                   => ['required_if:action,issue_pay', 'nullable', 'numeric', 'min:0.01'],
            'method'                   => ['required_if:action,issue_pay', 'nullable', 'in:especes,virement,cheque'],
            'reference'                => ['nullable', 'string', 'max:100'],
            'cheque_number'            => ['nullable', 'string', 'max:50'],
            'cheque_bank'              => ['nullable', 'string', 'max:100'],
            'cheque_due_date'          => ['required_if:method,cheque', 'nullable', 'date'],
            'transport_amount'         => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'transport_note'           => ['nullable', 'string', 'max:255'],
            'transport_remind_at'      => ['required_with:transport_amount', 'nullable', 'date', 'after_or_equal:today'],
            'transport_carrier'        => ['nullable', 'string', 'max:120'],
            'transport_phone'          => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\\s.\\-]{6,40}$/'],
            'installation_amount'      => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'installation_note'        => ['nullable', 'string', 'max:255'],
            'cheque_scan'              => PaymentDocumentService::RULE,
            'other_file'               => PaymentDocumentService::RULE,
        ]);

        if (! $request->user()->isAdmin() && $data['action'] === 'issue_pay') {
            throw ValidationException::withMessages(['action' => 'La facture doit etre approuvee avant l enregistrement d un paiement. Enregistrez d abord sans paiement.']);
        }

        // Le Global Scope renvoie 404 si le client n'appartient pas au commercial
        $client = Client::findOrFail($data['client_id']);
        $built  = $lines->build($data['items'], auth()->user()->isAdmin());

        // Transport / installation : ajoutés au total, sans TVA
        $transport    = round((float) ($data['transport_amount'] ?? 0), 2);
        $installation = round((float) ($data['installation_amount'] ?? 0), 2);
        $grandTotal   = round($built['total_ttc'] + $transport + $installation, 2);

        if ($data['action'] === 'issue_pay' && round((float) $data['amount'], 2) > $grandTotal + 0.001) {
            throw ValidationException::withMessages(['amount' => 'Le montant dépasse le total de la facture.']);
        }

        $due = isset($data['terms_days'])
            ? today()->addDays((int) $data['terms_days'])
            : (! empty($data['due_date'])
                ? Carbon::parse($data['due_date'])
                : today()->addDays((int) config('commerce.payment_days')));

        // Commercial: draft + mandatory director approval. Admin: direct issuance remains available.
        $invoice = DB::transaction(function () use ($client, $built, $data, $due, $service, $transport, $installation, $grandTotal, $request) {
            $inv = Invoice::create([
                'type'          => 'facture',
                'client_id'     => $client->id,
                'commercial_id' => $client->commercial_id,
                'status'        => 'brouillon', // état interne d'une fraction de seconde : émise juste après
                'issue_date'    => today(),
                'due_date'      => $due,
                'subtotal_ht'   => $built['subtotal_ht'],
                'tax_rate'      => $built['tax_rate'],
                'tax_amount'    => $built['tax_amount'],
                'transport_amount'    => $transport,
                'transport_note'      => $transport > 0 ? ($data['transport_note'] ?? null) : null,
                'transport_remind_at' => $transport > 0 ? ($data['transport_remind_at'] ?? null) : null,
                'transport_carrier'   => $transport > 0 ? ($data['transport_carrier'] ?? null) : null,
                'transport_phone'     => $transport > 0 ? ($data['transport_phone'] ?? null) : null,
                'installation_amount' => $installation,
                'installation_note'   => $installation > 0 ? ($data['installation_note'] ?? null) : null,
                'total_ttc'     => $grandTotal,
                'notes'         => $data['notes'] ?? null,
            ]);

            $inv->items()->createMany($built['lines']);

            return $request->user()->isAdmin() ? $service->issueLocked($inv->id, auth()->id()) : $inv;
        });

        if (! $request->user()->isAdmin()) {
            app(\App\Services\InvoiceIssueApproval::class)->request($invoice, $request->user()->id);
            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Brouillon enregistre. Validation du directeur demandee automatiquement.');
        }

        InvoiceIssued::dispatch($invoice);

        if ($data['action'] === 'issue_pay') {
            try {
                $payment = $payments->record($invoice, [
                    'amount'          => $data['amount'],
                    'method'          => $data['method'],
                    'paid_at'         => today()->toDateString(),
                    'reference'       => $data['reference'] ?? null,
                    'cheque_number'   => $data['cheque_number'] ?? null,
                    'cheque_bank'     => $data['cheque_bank'] ?? null,
                    'cheque_due_date' => $data['cheque_due_date'] ?? null,
                ], auth()->id());
            } catch (ValidationException $e) {
                return redirect()->to(route('invoices.show', $invoice) . '#paiement')
                    ->with('auto_pdf', route('invoices.pdf', [$invoice, 'download' => 1]))
                    ->withErrors(['payment' => 'Facture enregistrée, mais le paiement n\'a pas été enregistré : '
                        . collect($e->errors())->flatten()->first()]);
            }

            $fileError = app(PaymentDocumentService::class)->attachFromRequest($payment, $request, auth()->id());

            return redirect()->route('invoices.show', $invoice)
                ->withErrors($fileError ? ['file' => $fileError] : [])
                ->with('success', 'Facture ' . $invoice->number . ' enregistrée et paiement encaissé.')
                ->with('invoice_pdf', route('invoices.pdf', $invoice))
                ->with('auto_pdf', route('invoices.pdf', [$invoice, 'download' => 1]))
                ->with('receipt_pdf', route('payments.pdf', $payment));
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Facture ' . $invoice->number . ' enregistrée.')
            ->with('invoice_pdf', route('invoices.pdf', $invoice))
            ->with('auto_pdf', route('invoices.pdf', [$invoice, 'download' => 1]));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'commercial', 'items', 'order', 'parent', 'credit', 'payments.user', 'payments.documents']);

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Commande confirmée ou terminée -> facture créée ET émise directement.
     * (order_id est rempli : le stock reste géré par la commande / la livraison, pas ici.)
     */
    public function fromOrder(Order $order, InvoiceService $service)
    {
        // BL automatique : s'il n'y a pas encore de bon de livraison validé, on le crée avant la facture
        if (auth()->user()->isAdmin() && $order->status === 'confirmee'
            && ! $order->deliveries()->where('status', 'valide')->exists()) {
            app(DeliveryController::class)->autoDeliver($order);
        }

        $invoice = DB::transaction(function () use ($order, $service) {
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
                'status'        => 'brouillon', // état interne : émise juste après
                'issue_date'    => today(),
                'due_date'      => today()->addDays((int) config('commerce.payment_days')),
                'subtotal_ht'   => $o->subtotal_ht,
                'tax_rate'      => $o->tax_rate,
                'tax_amount'    => $o->tax_amount,
                'total_ttc'     => $o->total_ttc,
                'notes'         => $o->notes,
            ]);

            $invoice->items()->createMany(
                $o->items->map(fn ($i) => $i->only([
                    'product_id', 'name', 'sku', 'unit', 'quantity',
                    'unit_price', 'discount_percent', 'tax_rate', 'line_total_ht',
                ]))->all()
            );

            return auth()->user()->isAdmin() ? $service->issueLocked($invoice->id, auth()->id()) : $invoice;
        });

        if (! auth()->user()->isAdmin()) {
            app(\App\Services\InvoiceIssueApproval::class)->request($invoice, auth()->id());
            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Facture brouillon creee depuis la commande, en attente de validation du directeur.');
        }
        InvoiceIssued::dispatch($invoice);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Facture ' . $invoice->number . ' créée.');
    }

    /**
     * Échéance : modifiable après émission (elle ne change pas les montants).
     * - days = 0 / 15 / 30 / 60 : calculé à partir de la date de la facture
     * - due_date : date précise
     */
    public function update(Request $request, Invoice $invoice)
    {
        abort_unless(
            $invoice->type === 'facture'
                && in_array($invoice->status, ['brouillon', 'emise', 'partiellement_payee'], true),
            403,
            'Cette facture ne peut plus être modifiée.'
        );

        $data = $request->validate([
            'days'     => ['nullable', 'integer', 'in:0,15,30,60'],
            'due_date' => ['nullable', 'date', 'after_or_equal:' . $invoice->issue_date->format('Y-m-d')],
            'terms'    => ['nullable', 'string', 'max:3000'],
            'notes'    => ['nullable', 'string', 'max:3000'],
        ]);

        $payload = [];

        if (isset($data['days'])) {
            $payload['due_date'] = $invoice->issue_date->copy()->addDays((int) $data['days']);
        } elseif (! empty($data['due_date'])) {
            $payload['due_date'] = $data['due_date'];
        }

        if ($invoice->isDraft()) {
            foreach (['terms', 'notes'] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = $data[$field];
                }
            }
        }

        if ($payload === []) {
            return back();
        }

        $invoice->update($payload);

        return back()->with('success', 'Échéance mise à jour.');
    }

    /**
     * Transport / installation d'une facture déjà émise (ex. facture créée depuis une commande).
     * Autorisé seulement tant qu'aucun paiement ni avoir n'existe : les commissions et les soldes restent cohérents.
     */
    public function extras(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'transport_amount'    => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'transport_note'      => ['nullable', 'string', 'max:255'],
            'transport_remind_at' => ['required_with:transport_amount', 'nullable', 'date'],
            'transport_carrier'   => ['nullable', 'string', 'max:120'],
            'transport_phone'     => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\\s.\\-]{6,40}$/'],
            'installation_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'installation_note'   => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($invoice, $data) {
            $i = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($i->type !== 'facture' || $i->status !== 'emise'
                || (float) $i->amount_paid > 0 || Invoice::where('parent_id', $i->id)->exists()) {
                throw ValidationException::withMessages([
                    'extras' => 'Transport et installation ne se modifient que sur une facture émise, sans paiement ni avoir.',
                ]);
            }

            $t = round((float) ($data['transport_amount'] ?? 0), 2);
            $n = round((float) ($data['installation_amount'] ?? 0), 2);

            // Nouvelle date (ou premier transport) : l'alarme repart active. Même date : son état est conservé.
            $date    = $t > 0 ? ($data['transport_remind_at'] ?? null) : null;
            $changed = $date !== null && (! $i->transport_remind_at || $i->transport_remind_at->format('Y-m-d') !== $date);

            $i->update([
                'transport_amount'    => $t,
                'transport_note'      => $t > 0 ? ($data['transport_note'] ?? null) : null,
                'transport_remind_at' => $date,
                'transport_carrier'   => $t > 0 ? ($data['transport_carrier'] ?? null) : null,
                'transport_phone'     => $t > 0 ? ($data['transport_phone'] ?? null) : null,
                'transport_alarm_stopped_at' => ($changed || $t <= 0) ? null : $i->transport_alarm_stopped_at,
                'transport_alarm_stopped_by' => ($changed || $t <= 0) ? null : $i->transport_alarm_stopped_by,
                'installation_amount' => $n,
                'installation_note'   => $n > 0 ? ($data['installation_note'] ?? null) : null,
                'total_ttc'           => round((float) $i->subtotal_ht + (float) $i->tax_amount + $t + $n, 2),
            ]);
        });

        return back()->with('success', 'Transport / installation mis à jour.');
    }

    /** Sert uniquement à nettoyer d'anciens brouillons. */
    public function destroy(Invoice $invoice)
    {
        abort_unless($invoice->isDraft(), 403, 'Seul un brouillon peut être supprimé.');

        $invoice->delete(); // pas de numéro attribué => aucune fracture dans la numérotation

        return redirect()->route('invoices.index')->with('success', 'Brouillon supprimé.');
    }

    /** Sert uniquement à émettre d'anciens brouillons. */
    public function issue(Invoice $invoice, InvoiceService $service)
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Validation du directeur obligatoire.');
        $service->issue($invoice, auth()->id());

        return redirect()->to(route('invoices.show', $invoice) . '#paiement')
            ->with('auto_pdf', route('invoices.pdf', [$invoice, 'download' => 1]));
    }

    public function payStore(Request $request, Invoice $invoice, PaymentService $payments)
    {
        $data = $request->validate([
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'method'          => ['required', 'in:especes,virement,cheque'],
            'paid_at'         => ['required', 'date', 'before_or_equal:today'],
            'reference'       => ['nullable', 'string', 'max:255'],
            'cheque_number'   => ['required_if:method,cheque', 'nullable', 'string', 'max:50'],
            'cheque_bank'     => ['nullable', 'string', 'max:100'],
            'cheque_due_date' => ['required_if:method,cheque', 'nullable', 'date'],
            'cheque_scan'     => PaymentDocumentService::RULE,
            'other_file'      => PaymentDocumentService::RULE,
        ]);

        // PaymentService refuse un montant supérieur au reste à payer ou une facture non payable
        $payment = $payments->record($invoice, $data, auth()->id());

        $fileError = app(PaymentDocumentService::class)->attachFromRequest($payment, $request, auth()->id());

        return redirect()->route('invoices.show', $invoice)
            ->withErrors($fileError ? ['file' => $fileError] : [])
            ->with('success', 'Paiement ' . $payment->number . ' enregistré.')
            ->with('invoice_pdf', route('invoices.pdf', $invoice))
            ->with('auto_pdf', route('invoices.pdf', [$invoice, 'download' => 1]))
            ->with('receipt_pdf', route('payments.pdf', $payment));
    }

    /** Annulation = création d'un avoir complet (jamais de suppression) */
    public function credit(Invoice $invoice, DocumentNumberService $numbers, InvoiceService $service)
    {
        $credit = DB::transaction(function () use ($invoice, $numbers, $service) {
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
                'transport_amount'    => $i->transport_amount,
                'transport_note'      => $i->transport_note,
                'installation_amount' => $i->installation_amount,
                'installation_note'   => $i->installation_note,
                'total_ttc'     => $i->total_ttc,
                'notes'         => 'Avoir annulant la facture ' . $i->number,
            ]);

            $credit->items()->createMany(
                $i->items->map(fn ($it) => $it->only([
                    'product_id', 'name', 'sku', 'unit', 'quantity',
                    'unit_price', 'discount_percent', 'tax_rate', 'line_total_ht',
                ]))->all()
            );

            $service->restoreStock($i, $credit->number, auth()->id());
            $i->update(['status' => 'annulee']);

            return $credit;
        });

        InvoiceCancelled::dispatch($invoice->fresh());

        return redirect()->route('invoices.show', $credit)->with('success', 'Avoir créé, facture annulée.');
    }

    public function pdf(Request $request, Invoice $invoice)
    {
        abort_if($invoice->isDraft() && ! $request->user()->isAdmin(), 403, 'Facture en attente de validation.');
        abort_unless($request->user()->isAdmin() || app(\App\Services\DocumentReleaseApproval::class)->canRelease($invoice) || (! $invoice->isDraft() && app(\App\Services\InvoiceIssueApproval::class)->status($invoice) === 'approved'), 403);
        $invoice->load(['client', 'commercial', 'items', 'parent', 'payments']);

        $name = ($invoice->number ?? 'brouillon-' . $invoice->id) . '.pdf';
        $pdf  = Pdf::loadView('invoices.pdf', compact('invoice'))->setPaper('a4');

        return $request->boolean('download') ? $pdf->download($name) : $pdf->stream($name);
    }
}