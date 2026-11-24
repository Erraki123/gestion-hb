<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Validation\ValidationException;

/**
 * Construit les lignes d'un document (devis, facture).
 *  - ligne catalogue : prix et TVA viennent du produit (modifiables par l'admin seulement) ;
 *  - ligne libre (sans product_id) : nom, unité, prix et TVA saisis à la main, par tout le monde. Pas de stock.
 */
class DocumentLines
{
    public const FREE_UNITS = ['unité', 'pièce', 'heure', 'jour', 'forfait', 'lot', 'm', 'm²', 'kg'];

    public function __construct(private DocumentTotals $totals) {}

    public function build(array $items, bool $isAdmin): array
    {
        $allowed  = array_map('floatval', Setting::taxRates());
        $ids      = collect($items)->pluck('product_id')->filter()->values();
        $products = Product::whereIn('id', $ids)->where('is_active', true)->get()->keyBy('id');

        $lines = [];
        foreach ($items as $i => $item) {
            $lines[] = empty($item['product_id'])
                ? $this->freeLine($i, $item, $allowed)
                : $this->catalogLine($i, $item, $products->get($item['product_id']), $allowed, $isAdmin);
        }

        return $this->totals->compute($lines);
    }

    private function catalogLine(int $i, array $item, ?Product $product, array $allowed, bool $isAdmin): array
    {
        if (! $product) {
            throw ValidationException::withMessages(["items.$i.product_id" => 'Produit indisponible.']);
        }

        $price = ($isAdmin && isset($item['unit_price']))
            ? round((float) $item['unit_price'], 4)
            : (float) $product->sale_price;

        $rate = (float) $product->tax_rate;

        if ($isAdmin && isset($item['tax_rate']) && $item['tax_rate'] !== '') {
            $asked = (float) $item['tax_rate'];

            if (! in_array($asked, $allowed, true)) {
                throw ValidationException::withMessages(["items.$i.tax_rate" => 'Taux de TVA non autorisé.']);
            }

            $rate = $asked;
        }

        return [
            'product_id'       => $product->id,
            'name'             => $product->name,
            'sku'              => $product->sku,
            'unit'             => $product->unit,
            'quantity'         => (float) $item['quantity'],
            'unit_price'       => $price,
            'discount_percent' => (float) ($item['discount_percent'] ?? 0),
            'tax_rate'         => $rate,
        ];
    }

    private function freeLine(int $i, array $item, array $allowed): array
    {
        $name = trim((string) ($item['name'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages(["items.$i.name" => 'La désignation est obligatoire.']);
        }

        if (! isset($item['unit_price']) || $item['unit_price'] === '' || (float) $item['unit_price'] < 0) {
            throw ValidationException::withMessages(["items.$i.unit_price" => 'Le prix HT est obligatoire (0 accepté).']);
        }

        $unit = $item['unit'] ?? 'unité';
        if (! in_array($unit, self::FREE_UNITS, true)) {
            throw ValidationException::withMessages(["items.$i.unit" => 'Unité non autorisée.']);
        }

        $rate = (isset($item['tax_rate']) && $item['tax_rate'] !== '')
            ? (float) $item['tax_rate']
            : (float) config('commerce.tax_rate', 20);

        if (! in_array($rate, $allowed, true)) {
            throw ValidationException::withMessages(["items.$i.tax_rate" => 'Taux de TVA non autorisé.']);
        }

        return [
            'product_id'       => null,
            'name'             => mb_substr($name, 0, 255),
            'sku'              => '',
            'unit'             => $unit,
            'quantity'         => (float) $item['quantity'],
            'unit_price'       => round((float) $item['unit_price'], 4),
            'discount_percent' => (float) ($item['discount_percent'] ?? 0),
            'tax_rate'         => $rate,
        ];
    }
}
