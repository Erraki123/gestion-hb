<?php

namespace Tests\Feature;

use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HnbProductPriceHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        return Product::create([
            'name' => 'Produit prix QA', 'sku' => 'HNB-PRICE-QA',
            'unit' => 'piece', 'tax_rate' => 20,
            'sale_price' => 100, 'purchase_price' => 50,
            'stock_quantity' => 0, 'min_stock' => 1,
            'is_active' => true,
        ]);
    }

    private function payload(float $sale, float $purchase): array
    {
        return [
            'name' => 'Produit prix QA', 'sku' => 'HNB-PRICE-QA',
            'unit' => 'piece', 'tax_rate' => 20,
            'sale_price_input' => $sale, 'purchase_price' => $purchase,
            'min_stock' => 1, 'is_active' => 1,
        ];
    }

    public function test_admin_price_change_creates_history_with_actor_and_old_new_prices(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $product = $this->product();
        $this->actingAs($admin)->put(route('products.update', $product), $this->payload(130, 65))->assertRedirect();

        $this->assertDatabaseCount('price_histories', 1);
        $history = PriceHistory::firstOrFail();
        $this->assertSame($admin->id, $history->user_id);
        $this->assertSame($product->id, $history->product_id);
        $this->assertEqualsWithDelta(100, (float) $history->old_sale_price, 0.001);
        $this->assertEqualsWithDelta(130, (float) $history->new_sale_price, 0.001);
        $this->assertEqualsWithDelta(50, (float) $history->old_purchase_price, 0.001);
        $this->assertEqualsWithDelta(65, (float) $history->new_purchase_price, 0.001);
    }

    public function test_no_price_change_creates_no_price_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $product = $this->product();
        $this->actingAs($admin)->put(route('products.update', $product), $this->payload(100, 50))->assertRedirect();
        $this->assertDatabaseCount('price_histories', 0);
    }

    public function test_commercial_cannot_change_catalog_price_or_create_history(): void
    {
        $commercial = User::factory()->create(['role' => 'commercial', 'is_active' => true]);
        $product = $this->product();
        $this->actingAs($commercial)->put(route('products.update', $product), $this->payload(130, 65))->assertForbidden();
        $this->assertDatabaseCount('price_histories', 0);
        $this->assertEqualsWithDelta(100, (float) $product->fresh()->sale_price, 0.001);
    }

    public function test_fractional_catalog_price_change_is_recorded_without_silent_precision_loss(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $product = $this->product();
        $this->actingAs($admin)->put(route('products.update', $product), $this->payload(100.1234, 50.1234))->assertRedirect();

        $history = PriceHistory::firstOrFail();
        $this->assertEqualsWithDelta((float) $product->fresh()->sale_price, (float) $history->new_sale_price, 0.00005,
            'Price history lost precision relative to saved product price.');
        $this->assertEqualsWithDelta((float) $product->fresh()->purchase_price, (float) $history->new_purchase_price, 0.00005,
            'Purchase price history lost precision relative to saved product price.');
    }
}
