<?php
namespace App\Services;

use App\Models\Product;

final class HnbMarketPriceComparison
{
    /** Same currency, unit, SKU and tax basis must be verified by the operator. */
    public function compare(Product $product, string $basis, float $publicPrice): array
    {
        if (!in_array($basis, ['HT', 'TTC'], true) || !is_finite($publicPrice) || $publicPrice <= 0) {
            throw new \InvalidArgumentException('Prix ou base fiscale non valide.');
        }
        $ours = round($basis === 'HT' ? (float)$product->sale_price : $product->priceTtc(), 2);
        $public = round($publicPrice, 2);
        if ($public <= 0 || $ours <= 0) throw new \InvalidArgumentException('Un prix positif comparable est nécessaire.');
        return ['catalog_price'=>$ours,'public_price'=>$public,'difference'=>round($ours-$public,2)];
    }
}
