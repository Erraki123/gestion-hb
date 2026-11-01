<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    /**
     * @param string $type   entree | sortie | ajustement
     * @param float  $qty    entree/sortie : كمية موجبة ؛ ajustement : الرصيد الجديد المطلوب
     */
    public function move(
        Product $product,
        string $type,
        float $qty,
        int $userId,
        ?string $reason = null,
        ?string $reference = null
    ): StockMovement {
        return DB::transaction(function () use ($product, $type, $qty, $userId, $reason, $reference) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $current = (float) $locked->stock_quantity;

            $delta = match ($type) {
                'entree'     => $qty,
                'sortie'     => -$qty,
                'ajustement' => $qty - $current,
            };

            $new = $current + $delta;

            if ($new < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Stock insuffisant : disponible {$current}.",
                ]);
            }

            $locked->update(['stock_quantity' => $new]);

            return StockMovement::create([
                'product_id'  => $locked->id,
                'user_id'     => $userId,
                'type'        => $type,
                'quantity'    => $delta,
                'stock_after' => $new,
                'reason'      => $reason,
                'reference'   => $reference,
            ]);
        });
    }
}
