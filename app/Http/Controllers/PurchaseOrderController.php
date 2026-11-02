<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Setting;
use App\Models\Supplier;
use App\Services\DocumentNumberService;
use App\Services\DocumentTotals;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with('supplier')->latest('id')->paginate(15);

        return view('purchase-orders.index', compact('purchaseOrders'));
    }

    public function create()
    {
        return view('purchase-orders.create', ['suppliers' => Supplier::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['nullable', 'date'],
            'expected_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
        ]);

        $purchaseOrder = DB::transaction(function () use ($data, $request) {
            $order = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'],
                'status' => 'brouillon',
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
                'subtotal_ht' => 0,
                'tax_amount' => 0,
                'total_ttc' => 0,
            ]);

            foreach ($request->input('items', []) as $raw) {
                $product = Product::find($raw['product_id'] ?? null);
                $qty = (float) ($raw['quantity'] ?? 0);
                if ($qty <= 0 || ! $product) {
                    continue;
                }

                $unitCost = (float) ($raw['unit_cost'] ?? $product->purchase_price ?? 0);
                $discount = (float) ($raw['discount_percent'] ?? 0);
                $taxRate = (float) ($raw['tax_rate'] ?? $product->tax_rate ?? 0);
                $lineTotal = round($qty * $unitCost * (1 - $discount / 100), 2);

                $order->items()->create([
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit' => $product->unit,
                    'quantity' => $qty,
                    'unit_cost' => round($unitCost, 4),
                    'discount_percent' => $discount,
                    'tax_rate' => $taxRate,
                    'line_total_ht' => $lineTotal,
                    'received_quantity' => 0,
                ]);
            }

            $totals = app(DocumentTotals::class)->compute($order->items()->get()->map(fn ($item) => [
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_cost,
                'discount_percent' => (float) $item->discount_percent,
                'tax_rate' => (float) $item->tax_rate,
            ])->all());
            $order->subtotal_ht = $totals['subtotal_ht'];
            $order->tax_amount = $totals['tax_amount'];
            $order->total_ttc = $totals['total_ttc'];
            $order->save();

            return $order;
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('success', 'Commande fournisseur créée.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'items.product', 'receipts.items']);

        return view('purchase-orders.show', compact('purchaseOrder'));
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
            'notes' => ['nullable', 'string'],
        ]);

        $purchaseOrder->update([
            'expected_date' => $request->input('expected_date'),
            'notes' => $request->input('notes'),
        ]);

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('success', 'Commande fournisseur mise à jour.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        if (! $purchaseOrder->isDraft()) {
            abort(403, 'Seule une commande brouillon peut être supprimée.');
        }

        $purchaseOrder->delete();

        return redirect()->route('purchase-orders.index')->with('success', 'Commande fournisseur supprimée.');
    }

    public function confirm(PurchaseOrder $purchaseOrder)
    {
        if (! $purchaseOrder->isDraft()) {
            abort(403, 'La commande ne peut pas être confirmée.');
        }

        $purchaseOrder->number = app(DocumentNumberService::class)->next('BCF');
        $purchaseOrder->status = 'commande';
        $purchaseOrder->save();

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('success', 'Commande fournisseur confirmée.');
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->receipts()->where('status', 'valide')->exists()) {
            throw ValidationException::withMessages(['order' => 'Impossible d\'annuler une commande avec réception validée.']);
        }

        $purchaseOrder->status = 'annulee';
        $purchaseOrder->save();

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('success', 'Commande fournisseur annulée.');
    }

    public function pdf(PurchaseOrder $purchaseOrder)
    {
        return view('purchase-orders.pdf', compact('purchaseOrder'));
    }
}
