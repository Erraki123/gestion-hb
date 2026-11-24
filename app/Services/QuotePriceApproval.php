<?php

namespace App\Services;

use App\Models\Product;

class QuotePriceApproval
{
    /** Analyze computed lines, not browser-supplied values. */
    public function isRequired(array $lines): bool
    {
        $productPrices = Product::whereIn('id', collect($lines)->pluck('product_id')->filter()->all())
            ->get(['id', 'sale_price'])->keyBy('id');

        foreach ($lines as $line) {
            if ((float) ($line['discount_percent'] ?? 0) > 0.000001) return true;
            // A custom line is a manually selected selling price, therefore reviewed.
            if (empty($line['product_id'])) return true;
            $product = $productPrices->get($line['product_id']);
            if (! $product || abs((float) $line['unit_price'] - (float) $product->sale_price) > 0.0001) return true;
        }
        return false;
    }
}
