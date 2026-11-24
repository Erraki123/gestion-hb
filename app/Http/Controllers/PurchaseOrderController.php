<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        return redirect()->route('purchases.index', ['tab' => 'commandes']);
    }

    public function create(Request $request)
    {
        return view('purchase-orders.create', [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'selectedSupplier' => $request->query('supplier_id'),
            'products' => Product::where('is_active', true)->orderBy('name')->get()->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'purchase_price' => (float) $p->purchase_price,
                'tax_rate' => (float) $p->tax_rate,
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id'   => ['required', 'exists:suppliers,id'],
            'order_date'    => ['nullable', 'date'],
            'expected_date' => ['nullable', 'date'],
            'notes'         => ['nullable', 'string'],
            'items'         => ['required', 'array'],
        ]);

        $order = DB::transaction(function () use ($data, $request) {
            $order = PurchaseOrder::create([
                'supplier_id'   => $data['supplier_id'],
                'status'        => 'brouillon',
                'order_date'    => $data['order_date'] ?? now()->toDateString(),
                'expected_date' => $data['expected_date'] ?? null,
                'notes'         => $data['notes'] ?? null,
                'created_by'    => auth()->id(),
                'subtotal_ht'   => 0,
                'tax_amount'    => 0,
                'total_ttc'     => 0,
            ]);

            $groups = [];
            $subtotal = 0.0;
            $count = 0;

            foreach ($request->input('items', []) as $raw) {
                $product = Product::find($raw['product_id'] ?? null);
                $qty = (float) ($raw['quantity'] ?? 0);
                if ($qty <= 0 || ! $product) {
                    continue;
                }

                $unitCost = (float) ($raw['unit_cost'] ?? $product->purchase_price ?? 0);
                $discount = (float) ($raw['discount_percent'] ?? 0);
                $taxRate  = (float) ($raw['tax_rate'] ?? $product->tax_rate ?? 0);
                $lineHt   = round($qty * $unitCost * (1 - $discount / 100), 2);

                $order->items()->create([
                    'product_id'        => $product->id,
                    'name'              => $product->name,
                    'sku'               => $product->sku,
                    'unit'              => $product->unit,
                    'quantity'          => $qty,
                    'unit_cost'         => round($unitCost, 4),
                    'discount_percent'  => $discount,
                    'tax_rate'          => $taxRate,
                    'line_total_ht'     => $lineHt,
                    'received_quantity' => 0,
                ]);

                $key = number_format($taxRate, 2, '.', '');
                $groups[$key] = ($groups[$key] ?? 0) + $lineHt;
                $subtotal += $lineHt;
                $count++;
            }

            if ($count === 0) {
                throw ValidationException::withMessages(['items' => 'Ajoutez au moins un produit avec une quantité.']);
            }

            $tax = 0.0;
            foreach ($groups as $rate => $base) {
                $tax += round($base * (float) $rate / 100, 2);
            }

            $order->update([
                'subtotal_ht' => round($subtotal, 2),
                'tax_amount'  => round($tax, 2),
                'total_ttc'   => round($subtotal + $tax, 2),
            ]);

            return $order;
        });

        return redirect()->route('purchase-orders.show', $order)->with('success', 'Commande fournisseur créée.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'items.product', 'receipts']);

        $invoices = SupplierInvoice::where('purchase_order_id', $purchaseOrder->id)->latest('id')->get();

        return view('purchase-orders.show', compact('purchaseOrder', 'invoices'));
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'items']);

        return view('purchase-orders.edit', compact('purchaseOrder'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'expected_date' => ['nullable', 'date'],
            'notes'         => ['nullable', 'string'],
        ]);

        $purchaseOrder->update([
            'expected_date' => $request->input('expected_date'),
            'notes'         => $request->input('notes'),
        ]);

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('success', 'Commande fournisseur mise à jour.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        if (! $purchaseOrder->isDraft()) {
            abort(403, 'Seule une commande brouillon peut être supprimée.');
        }

        $purchaseOrder->delete();

        return redirect()->route('purchases.index', ['tab' => 'commandes'])->with('success', 'Commande fournisseur supprimée.');
    }

    public function confirm(PurchaseOrder $purchaseOrder)
    {
        if (! $purchaseOrder->isDraft()) {
            abort(403, 'La commande ne peut pas être confirmée.');
        }

        $purchaseOrder->number = app(DocumentNumberService::class)->next('BCF');
        $purchaseOrder->status = 'commande';
        $purchaseOrder->save();

        return back()->with('success', 'Commande fournisseur confirmée.');
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->receipts()->where('status', 'valide')->exists()) {
            throw ValidationException::withMessages(['order' => 'Impossible d\'annuler une commande avec réception validée.']);
        }

        $purchaseOrder->status = 'annulee';
        $purchaseOrder->save();

        return back()->with('success', 'Commande fournisseur annulée.');
    }

    public function pdf(PurchaseOrder $purchaseOrder)
    {
        return redirect()->route('admin.pdf-studio.show', ['type' => 'purchase_order', 'id' => $purchaseOrder->id, 'download' => 1]);
    }
}
