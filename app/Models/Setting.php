<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['name', 'value'];

    public const DEFAULT_RATES = [20.0, 14.0, 10.0, 7.0, 0.0];

    public static function allValues(): array
    {
        try {
            return Cache::rememberForever('settings.all', fn () => static::query()->pluck('value', 'name')->all());
        } catch (\Throwable) {
            return [];
        }
    }

    public static function get(string $name, mixed $default = null): mixed
    {
        $all = static::allValues();

        return (isset($all[$name]) && $all[$name] !== '') ? $all[$name] : $default;
    }

    public static function put(array $values): void
    {
        foreach ($values as $name => $value) {
            static::updateOrCreate(['name' => $name], ['value' => $value === null ? null : (string) $value]);
        }

        Cache::forget('settings.all');
    }

    public static function taxRates(): array
    {
        $raw = static::get('tax_rates');
        $rates = $raw ? json_decode($raw, true) : null;

        if (! is_array($rates) || ! $rates) {
            return self::DEFAULT_RATES;
        }

        return array_map('floatval', $rates);
    }

    public static function priceMode(): string
    {
        return static::get('price_entry_mode', 'ht') === 'ttc' ? 'ttc' : 'ht';
    }
}
