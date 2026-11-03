<?php

namespace App\Support;

class Money
{
    public static function words(float $amount): ?string
    {
        if (! class_exists(\NumberFormatter::class)) {
            return null; // امتداد intl غير مفعّل
        }

        $f     = new \NumberFormatter('fr', \NumberFormatter::SPELLOUT);
        $cents = (int) round($amount * 100);
        $int   = intdiv($cents, 100);
        $cts   = $cents % 100;

        $s = $f->format($int) . ' dirham' . ($int > 1 ? 's' : '');

        if ($cts > 0) {
            $s .= ' et ' . $f->format($cts) . ' centime' . ($cts > 1 ? 's' : '');
        }

        return ucfirst($s);
    }

    public static function price(float|string|null $v): string
    {
        $s = number_format((float) $v, 4, ',', ' ');
        $s = preg_replace('/(,\d\d)00$/', '$1', $s);
        $s = preg_replace('/(,\d\d\d)0$/', '$1', $s);

        return $s;
    }
}
