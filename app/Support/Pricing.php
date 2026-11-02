<?php

namespace App\Support;

use App\Models\Setting;

class Pricing
{
    /** Entered selling price (HT or TTC depending on the mode) -> HT stored with 4 decimals */
    public static function toHt(float $amount, float $taxRate, ?string $mode = null): float
    {
        $mode = $mode ?? Setting::priceMode();

        if ($mode === 'ttc') {
            return round($amount / (1 + ($taxRate / 100)), 4);
        }

        return round($amount, 4);
    }

    public static function toTtc(float $amount, float $taxRate, ?string $mode = null): float
    {
        $mode = $mode ?? Setting::priceMode();

        if ($mode === 'ht') {
            return round($amount * (1 + ($taxRate / 100)), 2);
        }

        return round($amount, 2);
    }

    public static function htToTtc(float $ht, float $taxRate): float
    {
        return round($ht * (1 + $taxRate / 100), 2);
    }

    /** Value to show in the price input according to the current entry mode */
    public static function display(float $ht, float $taxRate): float
    {
        return Setting::priceMode() === 'ttc' ? self::htToTtc($ht, $taxRate) : round($ht, 4);
    }
}
