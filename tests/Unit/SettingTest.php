<?php

namespace Tests\Unit;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_array_values_are_serialized_for_storage_and_read_back(): void
    {
        Setting::put([
            'tax_rates' => [20.0, 10.0, 0.0],
            'price_entry_mode' => 'ht',
        ]);

        $this->assertSame([20.0, 10.0, 0.0], Setting::taxRates());
        $this->assertSame('ht', Setting::priceMode());
    }
}
