<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function store(Request $request, Product $product, StockService $stock)
    {
        $data = $request->validate([
            'type'      => ['required', 'in:entree,sortie,ajustement'],
            'quantity'  => ['required', 'numeric', 'min:0'],
            'reason'    => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        if ($data['type'] !== 'ajustement' && (float) $data['quantity'] <= 0) {
            return back()->withErrors(['quantity' => 'La quantité doit être supérieure à 0.']);
        }

        $stock->move(
            $product, $data['type'], (float) $data['quantity'],
            auth()->id(), $data['reason'] ?? null, $data['reference'] ?? null
        );

        return back()->with('success', 'Mouvement de stock enregistré.');
    }
}
