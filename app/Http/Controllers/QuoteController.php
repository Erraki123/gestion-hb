<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Setting;
use App\Services\DocumentNumberService;
use App\Services\DocumentTotals;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteController extends Controller
{
    private const TRANSITIONS = [
        'brouillon' => ['envoye'],
        'envoye'    => ['accepte', 'refuse', 'expire'],
    ];

    public function index(Request $request)
    {
        $quotes = Quote::with(['client', 'commercial'])
            ->when($request->q, fn ($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('number', 'like', "%{$s}%")
                  ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('quotes.index', compact('quotes'));
    }

    public function create(Request $request)
    {
        return view('quotes.create', $this->formData() + [
            'quote'        => null,
            'initialItems' => [],
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
            ]);

            $quote->items()->createMany($built['lines']);

            return $quote;
        });

        return redirect()->route('quotes.show', $quote)->with('success', 'Devis créé.');
    }

    public function show(Quote $quote)
    {
        $quote->load(['client', 'commercial', 'items', 'order']);

        return view('quotes.show', compact('quote'));
    }

    public function edit(Quote $quote)
    {
        abort_unless($quote->isEditable(), 403, 'Seul un devis en brouillon peut être modifié.');

        $quote->load('items');

        $initial = $quote->items->map(fn ($i) => [
            'product_id'       => $i->product_id,
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
        abort_unless($quote->isEditable(), 403, 'Seul un devis en brouillon peut être modifié.');

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

            $quote->items()->delete();
            $quote->items()->createMany($built['lines']);
        });

        return redirect()->route('quotes.show', $quote)->with('success', 'Devis mis à jour.');
    }

    public function destroy(Quote $quote)
    {
        abort_unless($quote->isEditable(), 403, 'Seul un devis en brouillon peut être supprimé.');

        $quote->delete(); // Soft delete

        return redirect()->route('quotes.index')->with('success', 'Devis supprimé.');
    }

    public function status(Request $request, Quote $quote)
    {
        $to = $request->validate(['status' => ['required', 'in:envoye,accepte,refuse,expire']])['status'];

        if (! in_array($to, self::TRANSITIONS[$quote->status] ?? [], true)) {
            return back()->withErrors(['status' => 'Changement de statut non autorisé.']);
        }

        $quote->update(['status' => $to]);

        return back()->with('success', 'Statut mis à jour.');
    }

    public function convert(Quote $quote, DocumentNumberService $numbers)
    {
        $order = DB::transaction(function () use ($quote, $numbers) {
            // Verrou : empêche la double conversion (double-clic, 2 onglets...)
            $q = Quote::whereKey($quote->id)->lockForUpdate()->firstOrFail();

            if ($q->status !== 'accepte') {
                throw ValidationException::withMessages(['status' => 'Seul un devis accepté peut être converti.']);
            }

            if (Order::withTrashed()->where('quote_id', $q->id)->exists()) {
                throw ValidationException::withMessages(['status' => 'Ce devis a déjà été converti en commande.']);
            }

            $order = Order::create([
                'number'        => $numbers->next('CMD'),
                'quote_id'      => $q->id,
                'client_id'     => $q->client_id,
                'commercial_id' => $q->commercial_id,
                'status'        => 'nouvelle',
                'subtotal_ht'   => $q->subtotal_ht,
                'tax_rate'      => $q->tax_rate,
                'tax_amount'    => $q->tax_amount,
                'total_ttc'     => $q->total_ttc,
                'notes'         => $q->notes,
            ]);

            $order->items()->createMany(
                $q->items->map(fn ($i) => $i->only([
                    'product_id', 'name', 'sku', 'unit', 'quantity',
                    'unit_price', 'discount_percent', 'tax_rate', 'line_total_ht',
                ]))->all()
            );

            return $order;
        });

        return redirect()->route('orders.show', $order)->with('success', 'Commande créée à partir du devis.');
    }

    public function duplicate(Quote $quote, DocumentNumberService $numbers, DocumentTotals $totals)
    {
        $quote->load('items');

        $lines = $quote->items->map(fn ($i) => $i->only([
            'product_id', 'name', 'sku', 'unit', 'quantity', 'unit_price', 'discount_percent', 'tax_rate',
        ]))->all();

        $built = $totals->compute($lines);

        $copy = DB::transaction(function () use ($quote, $numbers, $built) {
            $new = Quote::create([
                'number'        => $numbers->next('DV'),
                'client_id'     => $quote->client_id,
                'commercial_id' => $quote->commercial_id,
                'status'        => 'brouillon',
                'issue_date'    => today(),
                'valid_until'   => today()->addDays(config('commerce.quote_validity_days')),
                'tax_rate'      => $built['tax_rate'],
                'subtotal_ht'   => $built['subtotal_ht'],
                'tax_amount'    => $built['tax_amount'],
                'total_ttc'     => $built['total_ttc'],
                'terms'         => $quote->terms,
                'notes'         => $quote->notes,
            ]);

            $new->items()->createMany($built['lines']);

            return $new;
        });

        return redirect()->route('quotes.show', $copy)
            ->with('success', 'Devis dupliqué en brouillon. Vérifiez les prix avant envoi.');
    }

    public function pdf(Quote $quote)
    {
        $quote->load(['client', 'commercial', 'items']);

        return Pdf::loadView('quotes.pdf', compact('quote'))
            ->setPaper('a4')
            ->stream($quote->number . '.pdf');
    }

    /* ----------------- helpers ----------------- */

    private function formData(): array
    {
        return [
            'clients'     => Client::orderBy('name')->get(['id', 'name', 'company']),
            'products'    => Product::where('is_active', true)->orderBy('name')
                ->get(['id', 'name', 'sku', 'unit', 'sale_price', 'tax_rate', 'stock_quantity']),
            'rates'       => Setting::taxRates(),
            'mode'        => Setting::priceMode(),
            'defaultRate' => (float) config('commerce.tax_rate'),
        ];
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
            'items.*.product_id'       => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'         => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'       => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate'         => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
    }

    private function buildLines(array $items, DocumentTotals $totals): array
    {
        $isAdmin = auth()->user()->isAdmin();
        $allowed = array_map('floatval', Setting::taxRates());
        $products = Product::whereIn('id', collect($items)->pluck('product_id'))
            ->where('is_active', true)->get()->keyBy('id');

        $lines = [];
        foreach ($items as $i => $item) {
            $product = $products->get($item['product_id']);

            if (! $product) {
                throw ValidationException::withMessages(["items.$i.product_id" => 'Produit indisponible.']);
            }

            $price = ($isAdmin && isset($item['unit_price']))
                ? round((float) $item['unit_price'], 4)
                : (float) $product->sale_price;

            $rate = (float) $product->tax_rate;

            if ($isAdmin && isset($item['tax_rate']) && $item['tax_rate'] !== '') {
                $asked = (float) $item['tax_rate'];

                if (! in_array($asked, $allowed, true)) {
                    throw ValidationException::withMessages(["items.$i.tax_rate" => 'Taux de TVA non autorisé.']);
                }

                $rate = $asked;
            }

            $lines[] = [
                'product_id'       => $product->id,
                'name'             => $product->name,
                'sku'              => $product->sku,
                'unit'             => $product->unit,
                'quantity'         => (float) $item['quantity'],
                'unit_price'       => $price,
                'discount_percent' => (float) ($item['discount_percent'] ?? 0),
                'tax_rate'         => $rate,
            ];
        }

        return $totals->compute($lines);
    }
}