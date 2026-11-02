<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Services\GoodsReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoodsReceiptController extends Controller
{
    public function store(Request $request, PurchaseOrder $purchaseOrder)
    {
        $data = $request->validate([
            'supplier_ref' => ['nullable', 'string', 'max:255'],
            'receipt_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $receipt = DB::transaction(function () use ($purchaseOrder, $data) {
            $draft = $purchaseOrder->receipts()->create([
                'supplier_id' => $purchaseOrder->supplier_id,
                'status' => 'brouillon',
                'receipt_date' => $data['receipt_date'] ?? now()->toDateString(),
                'supplier_ref' => $data['supplier_ref'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($purchaseOrder->items as $item) {
                $left = max(0, (float) $item->quantity - (float) $item->received_quantity);
                if ($left <= 0) {
                    continue;
                }

                $draft->items()->create([
                    'purchase_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'unit' => $item->unit,
                    'quantity' => $left,
                    'net_unit_cost' => (float) $item->unit_cost,
                ]);
            }

            return $draft;
        });

        return redirect()->route('receipts.show', $receipt)->with('success', 'Réception créée.');
    }

    public function show(GoodsReceipt $receipt)
    {
        $receipt->load(['purchaseOrder', 'items.product', 'supplier']);

        return view('receipts.show', compact('receipt'));
    }

    public function update(Request $request, GoodsReceipt $receipt)
    {
        if (! $receipt->isDraft()) {
            abort(403, 'Seule une réception brouillon peut être modifiée.');
        }

        $data = $request->validate([
            'receipt_date' => ['nullable', 'date'],
            'supplier_ref' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
        ]);

        $receipt->fill([
            'receipt_date' => $data['receipt_date'] ?? $receipt->receipt_date,
            'supplier_ref' => $data['supplier_ref'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
        $receipt->save();

        foreach ($request->input('items', []) as $id => $qty) {
            $item = GoodsReceiptItem::find($id);
            if ($item && $item->goods_receipt_id === $receipt->id) {
                $item->quantity = (float) $qty;
                $item->save();
            }
        }

        return redirect()->route('receipts.show', $receipt)->with('success', 'Réception mise à jour.');
    }

    public function destroy(GoodsReceipt $receipt)
    {
        if (! $receipt->isDraft()) {
            abort(403, 'Seule une réception brouillon peut être supprimée.');
        }

        $receipt->delete();

        return redirect()->route('purchase-orders.show', $receipt->purchaseOrder)->with('success', 'Réception supprimée.');
    }

    public function validate(GoodsReceipt $receipt, GoodsReceiptService $service)
    {
        $validated = $service->validate($receipt);

        return redirect()->route('receipts.show', $validated)->with('success', 'Réception validée.');
    }

    public function cancel(GoodsReceipt $receipt, GoodsReceiptService $service, Request $request)
    {
        $request->validate(['reason' => ['required', 'string', 'min:3']]);

        $service->cancel($receipt, $request->input('reason'));

        return redirect()->route('receipts.show', $receipt)->with('success', 'Réception annulée.');
    }
}
