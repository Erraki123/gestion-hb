<?php

namespace App\Support;

use App\Models\Setting;

class Pricing
{
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
}
