<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoodsReceiptService
{
    public function validate(GoodsReceipt $receipt): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt) {
            if (! $receipt->isDraft()) {
                throw ValidationException::withMessages(['receipt' => 'Cette réception n\'est pas un brouillon.']);
            }

            $receipt->load(['items.purchaseOrderItem', 'purchaseOrder.items']);
            $userId = auth()->id();
            $purchaseOrder = $receipt->purchaseOrder;

            if (! $purchaseOrder) {
                throw ValidationException::withMessages(['receipt' => 'Commande fournisseur introuvable.']);
            }

            $number = app(DocumentNumberService::class)->next('BR');
            $receipt->number = $number;
            $receipt->status = 'valide';
            $receipt->receipt_date = $receipt->receipt_date ?? now()->toDateString();
            $receipt->validated_at = now();
            $receipt->validated_by = $userId;
            $receipt->save();

            foreach ($receipt->items as $item) {
                $poItem = $item->purchaseOrderItem;
                if (! $poItem) {
                    throw ValidationException::withMessages(['quantity' => 'Ligne de commande introuvable.']);
                }

                $remaining = max(0, (float) $poItem->quantity - (float) $poItem->received_quantity);
                if ((float) $item->quantity > $remaining + 0.00001) {
                    throw ValidationException::withMessages(['quantity' => 'Quantité reçue supérieure à la quantité restante.']);
                }

                $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                $stock = (float) $product->stock_quantity;
                $this->applyPurchaseCost($product, (float) $item->net_unit_cost, $item->quantity, $userId, $number);
                app(StockService::class)->move($product, 'entree', (float) $item->quantity, $userId, 'Réception fournisseur', $number);

                $poItem->received_quantity = round((float) $poItem->received_quantity + (float) $item->quantity, 2);
                $poItem->save();

                $receipt->purchaseOrder->touch();
            }

            $this->refreshOrderStatus($purchaseOrder);

            return $receipt->fresh();
        });
    }

    public function cancel(GoodsReceipt $receipt, string $reason): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt, $reason) {
            if ($receipt->status !== 'valide') {
                throw ValidationException::withMessages(['receipt' => 'Seule une réception validée peut être annulée.']);
            }

            $reason = trim($reason);
            if ($reason === '') {
                throw ValidationException::withMessages(['reason' => 'Le motif est obligatoire.']);
            }

            $receipt->load('items.product');
            foreach ($receipt->items as $item) {
                $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                $stock = (float) $product->stock_quantity;
                if ($stock < (float) $item->quantity) {
                    throw ValidationException::withMessages(['quantity' => 'Stock insuffisant pour annuler la ligne ' . $item->name . '.']);
                }
                app(StockService::class)->move($product, 'sortie', (float) $item->quantity, auth()->id(), 'Annulation réception fournisseur', $receipt->number);

                $poItem = PurchaseOrderItem::whereKey($item->purchase_order_item_id)->first();
                if ($poItem) {
                    $poItem->received_quantity = round(max(0, (float) $poItem->received_quantity - (float) $item->quantity), 2);
                    $poItem->save();
                }
            }

            $receipt->status = 'annule';
            $receipt->cancel_reason = $reason;
            $receipt->cancelled_at = now();
            $receipt->cancelled_by = auth()->id();
            $receipt->save();

            $this->refreshOrderStatus($receipt->purchaseOrder);

            return $receipt->fresh();
        });
    }

    protected function applyPurchaseCost(Product $product, float $netUnitCost, float $qty, int $userId, string $reference): void
    {
        $method = Setting::get('purchase_cost_method', 'average');
        $oldCost = (float) $product->purchase_price;
        $stock = (float) $product->stock_quantity;

        if ($method === 'last') {
            $newCost = round($netUnitCost, 4);
        } else {
            $newQty = $stock + $qty;
            $newCost = $newQty > 0 ? round(($stock * $oldCost + $qty * $netUnitCost) / $newQty, 4) : round($netUnitCost, 4);
        }

        if (abs($oldCost - $newCost) > 0.00001) {
            PriceHistory::create([
                'product_id' => $product->id,
                'user_id' => $userId,
                'old_sale_price' => $product->sale_price,
                'new_sale_price' => $product->sale_price,
                'old_purchase_price' => $oldCost,
                'new_purchase_price' => $newCost,
            ]);
            $product->purchase_price = $newCost;
            $product->save();
        }
    }

    protected function refreshOrderStatus(?PurchaseOrder $purchaseOrder): void
    {
        if (! $purchaseOrder) {
            return;
        }

        $items = $purchaseOrder->items()->get();
        if ($items->isEmpty()) {
            $purchaseOrder->status = 'commande';
            $purchaseOrder->save();
            return;
        }

        $allDone = true;
        foreach ($items as $item) {
            if ((float) $item->received_quantity < (float) $item->quantity) {
                $allDone = false;
                break;
            }
        }

        $purchaseOrder->status = $allDone ? 'recue' : 'recue_partielle';
        if ($purchaseOrder->status === 'recue_partielle' && $items->every(fn ($item) => (float) $item->received_quantity === 0.0)) {
            $purchaseOrder->status = 'commande';
        }
        $purchaseOrder->save();
    }
}
