<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q'));

        if (mb_strlen($q) < 2) {
            return response()->json(['groups' => []]);
        }

        // Les Global Scopes limitent déjà un commercial à ses propres données
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $byClient = fn ($w) => $w->where('name', 'like', $like)->orWhere('company', 'like', $like);

        $groups = [];

        $items = Client::where(fn ($w) => $w->where('name', 'like', $like)
                ->orWhere('company', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('email', 'like', $like))
            ->orderBy('name')->limit(5)->get()
            ->map(fn ($c) => ['label' => $c->name, 'sub' => trim(($c->company ?: '') . ' ' . ($c->phone ?: '')), 'url' => route('clients.show', $c)]);
        $this->push($groups, 'Clients', $items);

        $items = Quote::with('client')
            ->where(fn ($w) => $w->where('number', 'like', $like)->orWhereHas('client', $byClient))
            ->latest('id')->limit(5)->get()
            ->map(fn ($m) => ['label' => $m->number, 'sub' => $m->client?->name, 'url' => route('quotes.show', $m)]);
        $this->push($groups, 'Devis', $items);

        $items = Order::with('client')
            ->where(fn ($w) => $w->where('number', 'like', $like)->orWhereHas('client', $byClient))
            ->latest('id')->limit(5)->get()
            ->map(fn ($m) => ['label' => $m->number, 'sub' => $m->client?->name, 'url' => route('orders.show', $m)]);
        $this->push($groups, 'Commandes', $items);

        $items = Invoice::with('client')
            ->where(fn ($w) => $w->where('number', 'like', $like)->orWhereHas('client', $byClient))
            ->latest('id')->limit(5)->get()
            ->map(fn ($m) => ['label' => $m->displayNumber(), 'sub' => $m->client?->name, 'url' => route('invoices.show', $m)]);
        $this->push($groups, 'Factures', $items);

        $items = Product::when(auth()->user()->isCommercial(), fn ($p) => $p->where('is_active', true))
            ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('sku', 'like', $like))
            ->orderBy('name')->limit(5)->get()
            ->map(fn ($p) => ['label' => $p->name, 'sub' => $p->sku, 'url' => route('products.show', $p)]);
        $this->push($groups, 'Produits', $items);

        return response()->json(['groups' => $groups]);
    }

    private function push(array &$groups, string $title, $items): void
    {
        if ($items->isNotEmpty()) {
            $groups[] = ['title' => $title, 'items' => $items->values()->all()];
        }
    }
}