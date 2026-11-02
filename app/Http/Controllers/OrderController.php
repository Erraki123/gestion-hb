<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    private const TRANSITIONS = [
        'nouvelle'  => ['confirmee', 'annulee'],
        'confirmee' => ['terminee', 'annulee'],
    ];

    public function index(Request $request)
    {
        $orders = Order::with(['client', 'commercial', 'items'])
            ->when($request->q, fn ($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('number', 'like', "%{$s}%")
                  ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['client', 'commercial', 'items', 'quote']);

        return view('orders.show', compact('order'));
    }

    public function status(Request $request, Order $order, StockService $stock)
    {
        $to = $request->validate(['status' => ['required', 'in:confirmee,terminee,annulee']])['status'];

        DB::transaction(function () use ($order, $to, $stock) {
            $o = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($to, self::TRANSITIONS[$o->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Changement de statut non autorisé.']);
            }

            $o->load('items');

            if ($to === 'confirmee') {
                if (! $o->stock_deducted && Setting::get('stock_deduction', 'order') === 'order') {
                    foreach ($o->items as $item) {
                        $stock->move($item->product()->firstOrFail(), 'sortie', (float) $item->quantity,
                            auth()->id(), 'Commande confirmée', $o->number);
                    }
                    $o->stock_deducted = true;
                }
            }

            if ($to === 'annulee') {
                if ($o->deliveries()->where('status', 'valide')->exists()) {
                    throw ValidationException::withMessages(['status' => 'Annulez d\'abord les bons de livraison']);
                }

                if ($o->stock_deducted) {
                    foreach ($o->items as $item) {
                        $stock->move($item->product()->firstOrFail(), 'entree', (float) $item->quantity,
                            auth()->id(), 'Commande annulée', $o->number);
                    }
                    $o->stock_deducted = false;
                }
            }

            $o->status = $to;
            $o->save();
        });

        return back()->with('success', 'Statut de la commande mis à jour.');
    }
}