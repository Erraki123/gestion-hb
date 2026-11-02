<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\Setting;
use App\Services\DocumentNumberService;
use App\Services\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $deliveries = Delivery::with(['client', 'commercial', 'order'])
            ->when($request->q, fn ($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('number', 'like', "%{$s}%")
                  ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('deliveries.index', compact('deliveries'));
    }

    public function storeFromOrder(Order $order)
    {
        $delivery = DB::transaction(function () use ($order) {
            $o = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($o->status !== 'confirmee') {
                throw ValidationException::withMessages(['status' => 'La commande doit être confirmée avant la création d’un bon de livraison.']);
            }

            $o->load('items');
            $rows = [];

            foreach ($o->items as $item) {
                $remaining = (float) $item->remainingQuantity();
                if ($remaining > 0) {
                    $rows[] = [
                        'order_item_id' => $item->id,
                        'product_id'    => $item->product_id,
                        'name'          => $item->name,
                        'sku'           => $item->sku,
                        'unit'          => $item->unit,
                        'quantity'      => $remaining,
                    ];
                }
            }

            if ($rows === []) {
                throw ValidationException::withMessages(['status' => 'Aucune quantité restante à livrer.']);
            }

            $d = Delivery::create([
                'order_id'     => $o->id,
                'client_id'    => $o->client_id,
                'commercial_id'=> $o->commercial_id,
                'status'       => 'brouillon',
            ]);

            $d->items()->createMany($rows);

            return $d;
        });

        return redirect()->route('deliveries.show', $delivery)
            ->with('success', 'Bon de livraison créé en brouillon.');
    }

    public function show(Delivery $delivery)
    {
        $delivery->load(['client', 'commercial', 'order', 'items.orderItem']);

        return view('deliveries.show', compact('delivery'));
    }

    public function update(Request $request, Delivery $delivery)
    {
        abort_unless($delivery->isDraft(), 403, 'Seul un brouillon peut être modifié.');

        $data = $request->validate([
            'delivery_date' => ['nullable', 'date'],
            'received_by'   => ['nullable', 'string', 'max:120'],
            'notes'         => ['nullable', 'string', 'max:3000'],
            'items'         => ['nullable', 'array'],
            'items.*'       => ['nullable', 'numeric', 'min:0'],
        ]);

        $delivery->update([
            'delivery_date' => $data['delivery_date'] ?? $delivery->delivery_date,
            'received_by'   => $data['received_by'] ?? null,
            'notes'         => $data['notes'] ?? null,
        ]);

        foreach ($request->input('items', []) as $deliveryItemId => $qty) {
            $item = $delivery->items()->findOrFail($deliveryItemId);
            $q = (float) $qty;
            $remaining = $item->orderItem ? (float) $item->orderItem->remainingQuantity() : 0;

            if ($q > $remaining) {
                throw ValidationException::withMessages(['items' => 'Quantité invalide pour le produit ' . $item->name . '.']);
            }

            $item->update(['quantity' => $q]);
        }

        $delivery->items()->where('quantity', 0)->delete();

        return back()->with('success', 'Bon de livraison mis à jour.');
    }

    public function destroy(Delivery $delivery)
    {
        abort_unless($delivery->isDraft(), 403, 'Seul un brouillon peut être supprimé.');

        $delivery->items()->delete();
        $delivery->delete();

        return redirect()->route('deliveries.index')->with('success', 'Brouillon supprimé.');
    }

    public function validate(Delivery $delivery, DocumentNumberService $numbers, StockService $stock)
    {
        $delivery = DB::transaction(function () use ($delivery, $numbers, $stock) {
            $d = Delivery::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($d->order_id)->lockForUpdate()->firstOrFail();

            if (! $d->isDraft()) {
                throw ValidationException::withMessages(['status' => 'Ce bon de livraison est déjà validé.']);
            }

            if ($order->status !== 'confirmee') {
                throw ValidationException::withMessages(['status' => 'La commande doit être confirmée.']);
            }

            $d->load('items.orderItem');
            $items = $d->items->filter(fn ($item) => (float) $item->quantity > 0);

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Aucun article à livrer.']);
            }

            foreach ($items as $item) {
                $remaining = (float) ($item->orderItem ? $item->orderItem->remainingQuantity() : 0);
                if ((float) $item->quantity > $remaining) {
                    throw ValidationException::withMessages(['items' => 'Quantité invalide pour le produit ' . $item->name . '.']);
                }
            }

            $d->number = $numbers->next('BL');
            $d->delivery_date = $d->delivery_date ?? today();
            $d->status = 'valide';
            $d->validated_at = now();
            $d->validated_by = auth()->id();

            if (! $order->stock_deducted) {
                foreach ($items as $item) {
                    $stock->move(
                        $item->product()->firstOrFail(),
                        'sortie',
                        (float) $item->quantity,
                        auth()->id(),
                        'Bon de livraison',
                        $d->number
                    );
                }
                $d->stock_moved = true;
                $order->stock_deducted = true;
            }

            $d->save();
            $order->save();

            return $d->fresh(['items.orderItem', 'client', 'commercial', 'order']);
        });

        return redirect()->route('deliveries.show', $delivery)
            ->with('success', 'Bon de livraison validé.');
    }

    public function cancel(Request $request, Delivery $delivery, StockService $stock)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        abort_unless(auth()->user()->isAdmin(), 403, 'Seuls les administrateurs peuvent annuler un bon de livraison.');
        abort_unless($delivery->status === 'valide', 403, 'Seul un bon validé peut être annulé.');

        DB::transaction(function () use ($delivery, $data, $stock) {
            $d = Delivery::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($d->order_id)->lockForUpdate()->firstOrFail();

            if ($d->stock_moved) {
                foreach ($d->items as $item) {
                    $stock->move(
                        $item->product()->firstOrFail(),
                        'entree',
                        (float) $item->quantity,
                        auth()->id(),
                        'Bon de livraison annulé',
                        $d->number . ' (annulé)'
                    );
                }
            }

            $d->status = 'annule';
            $d->stock_moved = false;
            $d->cancelled_at = now();
            $d->cancelled_by = auth()->id();
            $d->cancel_reason = $data['reason'];
            $d->save();

            if (Setting::get('stock_deduction', 'order') === 'delivery'
                && $order->stock_deducted
                && ! $order->deliveries()->where('status', 'valide')->whereKeyNot($d->id)->exists()) {
                $order->stock_deducted = false;
                $order->save();
            }
        });

        return back()->with('success', 'Bon de livraison annulé.');
    }

    public function pdf(Delivery $delivery)
    {
        $delivery->load(['order', 'client', 'items.product']);

        return Pdf::loadView('deliveries.pdf', compact('delivery'))
            ->setPaper('a4')
            ->stream(($delivery->number ?? 'brouillon-' . $delivery->id) . '.pdf');
    }
}
