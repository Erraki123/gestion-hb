<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Quote;
use App\Services\DocumentLines;
use App\Services\DocumentNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Recreate a quote as an UNAPPROVED draft. Never copy release approvals,
 * orders, transmissions or other workflow state from the original.
 */
class QuoteDuplicateController extends Controller
{
    public function __invoke(Quote $quote, DocumentNumberService $numbers, DocumentLines $builder): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user && ($user->isAdmin() || $user->isCommercial()), 403);

        // Retain the model's owner global scope and verify ownership explicitly.
        if ($user->isCommercial()) {
            abort_unless((int) $quote->commercial_id === (int) $user->id, 404);
        }

        // Do not resurrect deleted / foreign clients through quote duplication.
        $client = Client::query()->findOrFail($quote->client_id);
        if ($user->isCommercial()) {
            abort_unless((int) $client->commercial_id === (int) $user->id, 404);
        }

        $quote->loadMissing('items');
        if ($quote->items->isEmpty() || $quote->items->count() > 100) {
            throw ValidationException::withMessages([
                'items' => 'Le devis ne contient pas de lignes valides à dupliquer.',
            ]);
        }

        $sourceLines = $quote->items->map(fn ($line) => $line->only([
            'product_id', 'name', 'sku', 'unit', 'quantity',
            'unit_price', 'discount_percent', 'tax_rate',
        ]))->all();

        // Revalidate active catalog references; only the director may carry
        // custom catalog prices over. Commercial copies use live catalog prices.
        // Discounted drafts still require director approval before release.
        $built = $builder->build($sourceLines, $user->isAdmin());

        $copy = DB::transaction(function () use ($quote, $client, $numbers, $built) {
            $copy = Quote::create([
                'number' => $numbers->next('DV'),
                'client_id' => $client->id,
                'commercial_id' => $quote->commercial_id,
                'status' => 'brouillon',
                'issue_date' => today(),
                'valid_until' => today()->addDays(max(1, (int) config('commerce.quote_validity_days', 15))),
                'tax_rate' => $built['tax_rate'],
                'subtotal_ht' => $built['subtotal_ht'],
                'tax_amount' => $built['tax_amount'],
                'total_ttc' => $built['total_ttc'],
                'terms' => $quote->terms,
                'notes' => $quote->notes,
            ]);
            $copy->items()->createMany($built['lines']);

            return $copy;
        });

        // Intentionally NO auto-send, approval, conversion, payment or stock action.
        return redirect()->route('quotes.show', $copy)
            ->with('success', 'Devis dupliqué en brouillon. Validation requise avant tout envoi.');
    }
}
