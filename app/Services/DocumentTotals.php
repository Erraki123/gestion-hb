<?php

namespace App\Services;

class DocumentTotals
{
    /**
     * Each line: quantity, unit_price (HT), discount_percent, tax_rate.
     * VAT is computed PER RATE on the sum of bases (not line by line).
     */
    public function compute(array $lines): array
    {
        $groups   = [];
        $subtotal = 0.0;

        foreach ($lines as &$l) {
            $gross = (float) $l['quantity'] * (float) $l['unit_price'];
            $l['line_total_ht'] = round($gross * (1 - ((float) ($l['discount_percent'] ?? 0) / 100)), 2);

            $key = number_format((float) ($l['tax_rate'] ?? 0), 2, '.', '');
            $groups[$key] = ($groups[$key] ?? 0.0) + $l['line_total_ht'];
            $subtotal += $l['line_total_ht'];
        }
        unset($l);

        ksort($groups, SORT_NUMERIC);

        $breakdown = [];
        $tax = 0.0;

        foreach ($groups as $rate => $base) {
            $base = round($base, 2);
            $t    = round($base * (float) $rate / 100, 2);
            $breakdown[] = ['rate' => (float) $rate, 'base' => $base, 'tax' => $t];
            $tax += $t;
        }

        $subtotal = round($subtotal, 2);
        $tax      = round($tax, 2);

        return [
            'lines'         => $lines,
            'subtotal_ht'   => $subtotal,
            'tax_amount'    => $tax,
            'total_ttc'     => round($subtotal + $tax, 2),
            'tax_breakdown' => $breakdown,
            'tax_rate'      => count($breakdown) === 1 ? $breakdown[0]['rate'] : 0.0,
        ];
    }

    /** VAT breakdown of a stored document (same rounding as compute) */
    public static function breakdown(iterable $items): array
    {
        $groups = [];

        foreach ($items as $i) {
            $key = number_format((float) $i->tax_rate, 2, '.', '');
            $groups[$key] = ($groups[$key] ?? 0.0) + (float) $i->line_total_ht;
        }

        ksort($groups, SORT_NUMERIC);

        $out = [];
        foreach ($groups as $rate => $base) {
            $base  = round($base, 2);
            $out[] = ['rate' => (float) $rate, 'base' => $base, 'tax' => round($base * (float) $rate / 100, 2)];
        }

        return $out;
    }
}
