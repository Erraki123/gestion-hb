<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\User;
use App\Services\DocumentNumberService;
use App\Services\DocumentTotals;
use App\Services\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteController extends Controller
{
    // Seule transition manuelle gardée : « Le client refuse ».
    // (envoyé / accepté / expiré ne sont plus des boutons : conversion = acceptation, expiré = automatique)
    private const TRANSITIONS = [
        'brouillon' => ['refuse'],
        'envoye'    => ['refuse'],
        'accepte'   => ['refuse'],
    ];

    public function index(Request $request)
    {
        $isAdmin = auth()->user()->isAdmin();
        $term    = trim((string) $request->input('q'));
        $client  = trim((string) $request->input('client'));

        $quotes = Quote::with(['client', 'commercial', 'order.items', 'order.invoice'])
            ->when($term !== '', function ($q) use ($term) {
                $like = '%' . $this->escapeLike($term) . '%';
                $q->where(fn ($w) => $w->where('number', 'like', $like)
                    ->orWhereHas('order', fn ($o) => $o->where('number', 'like', $like)));
            })
            ->when($client !== '', fn ($q) => $q->whereHas('client',
                fn ($c) => $c->where('name', 'like', '%' . $this->escapeLike($client) . '%')))
            ->when($isAdmin && $request->filled('commercial_id'),
                fn ($q) => $q->where('commercial_id', (int) $request->input('commercial_id')))
            ->when($request->status, function ($q, $s) {
                // Filtre sur l'étape affichée
                match ($s) {
                    'devis'    => $q->doesntHave('order')->where('status', '!=', 'refuse')
                                    ->where(fn ($w) => $w->whereNull('valid_until')
                                        ->orWhereDate('valid_until', '>=', today())),
                    'expire'   => $q->doesntHave('order')->where('status', '!=', 'refuse')
                                    ->whereDate('valid_until', '<', today()),
                    'refuse'   => $q->doesntHave('order')->where('status', 'refuse'),
                    'commande' => $q->whereHas('order', fn ($o) => $o->where('status', '!=', 'annulee')
                                    ->whereDoesntHave('invoice')),
                    'facture'  => $q->whereHas('order.invoice'),
                    default    => $q,
                };
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Appel AJAX du filtre : seulement les lignes et la pagination
        if ($request->expectsJson()) {
            return response()->json([
                'rows'  => view('quotes._rows', ['quotes' => $quotes])->render(),
                'links' => (string) $quotes->links(),
                'total' => $quotes->total(),
            ]);
        }

        $commerciaux = $isAdmin
            ? User::where('role', 'commercial')->orderBy('name')->get()
            : collect();

        return view('quotes.index', compact('quotes', 'commerciaux'));
    }

    /**
     * Recherche rapide de produits (AJAX) pour les lignes du devis.
     * Le commercial ne reçoit jamais le prix d'achat.
     */
    public function searchProducts(Request $request)
    {
        $s = trim((string) $request->query('q', ''));

        $products = Product::where('is_active', true)
            ->when($s !== '', function ($q) use ($s) {
                $like = '%' . $this->escapeLike($s) . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('sku', 'like', $like));
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'sku', 'unit', 'sale_price', 'tax_rate', 'stock_quantity']);

        return response()->json($products->map(fn ($p) => [
            'id'    => $p->id,
            'label' => $p->name,
            'sub'   => $p->sku . ' · stock ' . ($p->stock_quantity + 0) . ' · ' . number_format($p->sale_price, 2, ',', ' ') . ' DH HT',
            'data'  => [
                'sale_price' => (float) $p->sale_price,
                'tax_rate'   => (float) $p->tax_rate,
                'unit'       => $p->unit,
                'stock'      => (float) $p->stock_quantity,
            ],
        ])->values());
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    /** Data required by quotes._form for create and edit. */
    private function formData(): array
    {
        return [
            'mode' => Setting::priceMode(),
            'rates' => Setting::taxRates(),
            'defaultRate' => (float) config('commerce.tax_rate', 20),
        ];
    }

    public function create(Request $request)
    {
        return view('quotes.create', $this->formData() + [
            'quote'          => null,
            'initialItems'   => [],
            'selectedClient' => $request->client_id,
        ]);
    }

    public function store(Request $request, DocumentNumberService $numbers, DocumentTotals $totals)
    {
        $data = $this->validated($request);

        // Le Global Scope renvoie 404 si le client n'appartient pas au commercial
        $client = Client::findOrFail($data['client_id']);
        $built  = $this->buildLines($data['items'], $totals);

        $quote = DB::transaction(function () use ($data, $client, $built, $numbers) {
            $quote = Quote::create([
                'number'        => $numbers->next('DV'),
                'client_id'     => $client->id,
                'commercial_id' => $client->commercial_id,
                'status'        => 'brouillon',
                'issue_date'    => $data['issue_date'],
                'valid_until'   => $data['valid_until'] ?? null,
                'tax_rate'      => $built['tax_rate'],
                'subtotal_ht'   => $built['subtotal_ht'],
                'tax_amount'    => $built['tax_amount'],
                'total_ttc'     => $built['total_ttc'],
                'terms'         => $data['terms'] ?? null,
                'notes'         => $data['notes'] ?? null,
                'requires_approval' => true,
                'approval_status' => 'pending',
                'approval_requested_by' => auth()->id(),
                'approval_requested_at' => now(),
            ]);

            $quote->items()->createMany($built['lines']);

            return $quote;
        });

        // Envoi automatique au client (e-mail + WhatsApp) si activé dans le .env
        $sent = 0;
        try {
            $sent = \App\Services\DocumentSender::queue($quote);
        } catch (\Throwable $e) {
            report($e); // un souci d'envoi ne doit jamais bloquer la création du devis
        }

        // Après l'enregistrement : retour à la table
        return redirect()->route('quotes.index')
            ->with('success', 'Devis ' . $quote->number . ' créé.' . ($sent ? ' Envoi au client en cours.' : ''));
    }

    public function show(Quote $quote)
    {
        $quote->load(['client', 'commercial', 'items', 'order.items', 'order.invoice', 'order.deliveries']);

        return view('quotes.show', compact('quote'));
    }

    public function edit(Quote $quote)
    {
        abort_unless($quote->isEditable(), 403, 'Ce devis ne peut plus être modifié (déjà converti ou refusé).');

        $quote->load('items');

        $initial = $quote->items->map(fn ($i) => [
            'product_id'       => $i->product_id,
            'product_label'    => $i->name,
            'name'             => $i->name,
            'unit'             => $i->unit,
            'is_free'          => $i->product_id === null,
            'quantity'         => (float) $i->quantity,
            'unit_price'       => (float) $i->unit_price,
            'tax_rate'         => (float) $i->tax_rate,
            'discount_percent' => (float) $i->discount_percent,
        ])->values();

        return view('quotes.edit', $this->formData() + [
            'quote'          => $quote,
            'initialItems'   => $initial,
            'selectedClient' => $quote->client_id,
        ]);
    }

    public function update(Request $request, Quote $quote, DocumentTotals $totals)
    {
        abort_unless($quote->isEditable(), 403, 'Ce devis ne peut plus être modifié (déjà converti ou refusé).');

        $data   = $this->validated($request);
        $client = Client::findOrFail($data['client_id']);
        $built  = $this->buildLines($data['items'], $totals);

        DB::transaction(function () use ($quote, $data, $client, $built) {
            $quote->update([
                'client_id'     => $client->id,
                'commercial_id' => $client->commercial_id,
                'issue_date'    => $data['issue_date'],
                'valid_until'   => $data['valid_until'] ?? null,
                'tax_rate'      => $built['tax_rate'],
                'subtotal_ht'   => $built['subtotal_ht'],
                'tax_amount'    => $built['tax_amount'],
                'total_ttc'     => $built['total_ttc'],
                'terms'         => $data['terms'] ?? null,
                'notes'         => $data['notes'] ?? null,
            ]);

            $sensitive = true;
            $quote->update([
                'requires_approval' => $sensitive, 'approval_status' => $sensitive ? 'pending' : null,
                'approval_requested_by' => auth()->id(), 'approval_requested_at' => now(),
                'approval_decided_by' => null, 'approval_decided_at' => null, 'approval_comment' => null,
            ]);
            $quote->items()->delete();
            $quote->items()->createMany($built['lines']);
        });

        return redirect()->route('quotes.show', $quote)->with('success', 'Devis mis à jour.');
    }

    public function destroy(Quote $quote)
    {
        abort_unless($quote->isEditable(), 403, 'Ce devis ne peut plus être supprimé (déjà converti ou refusé).');

        $quote->delete(); // Soft delete

        return redirect()->route('quotes.index')->with('success', 'Devis supprimé.');
    }

    public function status(Request $request, Quote $quote)
    {
        $to = $request->validate(['status' => ['required', 'in:envoye,accepte,refuse,expire']])['status'];

        $quote->loadMissing('order');

        if ($quote->order || ! in_array($to, self::TRANSITIONS[$quote->status] ?? [], true)) {
            return back()->withErrors(['status' => 'Changement de statut non autorisé.']);
        }

        $quote->update(['status' => $to]);

        return back()->with('success', 'Statut mis à jour.');
    }

    public function pdf(Request $request, Quote $quote)
    {
        // Directeur may review any quote; commercial cannot access unapproved PDF.
        abort_unless($request->user()->isAdmin() || $quote->approval_status === 'approved', 403);
        $quote->load(['client', 'commercial', 'items', 'order']);
        $name = ($quote->number ?: 'devis-'.$quote->id).'.pdf';
        $pdf = Pdf::loadView('quotes.pdf', compact('quote'))->setPaper('a4');
        return $request->boolean('download') ? $pdf->download($name) : $pdf->stream($name);
    }

    public function convert(Quote $quote, DocumentNumberService $numbers, StockService $stock)
    {
        $order = DB::transaction(function () use ($quote, $numbers, $stock) {
            // Verrou : empêche la double conversion (double-clic, 2 onglets...)
            $q = Quote::whereKey($quote->id)->lockForUpdate()->firstOrFail();

            if (! in_array($q->status, ['brouillon', 'envoye', 'accepte'], true)) {
                throw ValidationException::withMessages(['status' => 'Un devis refusé ne peut pas être converti. Dupliquez-le pour en créer un nouveau.']);
            }

            if (Order::withTrashed()->where('quote_id', $q->id)->exists()) {
                throw ValidationException::withMessages(['status' => 'Ce devis a déjà été converti en commande.']);
            }

            // HNB_ADMIN_CLIENT_ACCEPT_V1
            // An explicit Admin client-accept action also records
            // the director's approval before conversion.
            if (auth()->user()->isAdmin() && $q->approval_status !== 'approved') {
                $q->update([
                    'approval_status' => 'approved',
                    'approval_decided_by' => auth()->id(),
                    'approval_decided_at' => now(),
                    'approval_comment' => 'Validation direction lors de l acceptation client',
                ]);
            }

            if ($q->approval_status !== 'approved') {
                throw ValidationException::withMessages(['approval' => 'Ce devis doit etre approuve par le directeur avant conversion.']);
            }

            // Convertir = le client a accepté : on enregistre l'acceptation automatiquement
            if ($q->status !== 'accepte') {
                $q->update(['status' => 'accepte']);
            }

            $order = Order::create([
                'number'        => $numbers->next('CMD'),
                'quote_id'      => $q->id,
                'client_id'     => $q->client_id,
                'commercial_id' => $q->commercial_id,
                'status'        => 'nouvelle', // confirmation réservée à l'administration
                'subtotal_ht'   => $q->subtotal_ht,
                'tax_rate'      => $q->tax_rate,
                'tax_amount'    => $q->tax_amount,
                'total_ttc'     => $q->total_ttc,
                'notes'         => $q->notes,

                // HNB_ADMIN_ORDER_NO_DOUBLE_APPROVAL
                'approval_status' => auth()->user()->isAdmin() ? 'approved' : 'pending',
                'approval_requested_by' => auth()->id(),
                'approval_requested_at' => now(),
                'approval_decided_by' => auth()->user()->isAdmin() ? auth()->id() : null,
                'approval_decided_at' => auth()->user()->isAdmin() ? now() : null,
            ]);

            $order->items()->createMany(
                $q->items->map(fn ($i) => $i->only([
                    'product_id', 'name', 'sku', 'unit', 'quantity',
                    'unit_price', 'discount_percent', 'tax_rate', 'line_total_ht',
                ]))->all()
            );

            return $order;
        });

        return redirect()->route('orders.show', $order)
            ->with('success', 'Commande créée en attente de validation administrative. Aucun stock déduit.');
    }


    private function validated(Request $request): array
    {
        return $request->validate([
            'client_id'                => ['required', 'integer'],
            'issue_date'               => ['required', 'date'],
            'valid_until'              => ['nullable', 'date', 'after_or_equal:issue_date'],
            'terms'                    => ['nullable', 'string', 'max:3000'],
            'notes'                    => ['nullable', 'string', 'max:3000'],
            'items'                    => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id'       => ['nullable', 'integer', 'exists:products,id'],
            'items.*.name'             => ['required_without:items.*.product_id', 'nullable', 'string', 'max:255'],
            'items.*.unit'             => ['nullable', 'string', 'max:20'],
            'items.*.quantity'         => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'       => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate'         => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
    }

    private function buildLines(array $items, DocumentTotals $totals): array
    {
        return app(\App\Services\DocumentLines::class)->build($items, auth()->user()->isAdmin());
    }
}