<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HnbQuotePricingPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function actorsAndProduct(): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $commercial = User::factory()->create(['role' => 'commercial', 'is_active' => true]);
        $client = Client::create([
            'commercial_id' => $commercial->id,
            'name' => 'Client Pricing QA',
            'status' => 'actif',
        ]);
        $product = Product::create([
            'name' => 'Produit Pricing QA',
            'sku' => 'PRICING-QA-001',
            'unit' => 'pièce',
            'sale_price' => 100,
            'purchase_price' => 50,
            'tax_rate' => 20,
            'stock_quantity' => 5,
            'min_stock' => 1,
            'is_active' => true,
        ]);
        return [$admin, $commercial, $client, $product];
    }

    private function quotePayload(Client $client, Product $product, float $price, float $discount): array
    {
        return [
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => $price,
                'tax_rate' => 20,
                'discount_percent' => $discount,
            ]],
        ];
    }

    public function test_commercial_cannot_override_catalog_price_by_tampering_with_request(): void
    {
        [, $commercial, $client, $product] = $this->actorsAndProduct();
        $this->actingAs($commercial)->post(route('quotes.store'), $this->quotePayload($client, $product, 1, 0))->assertRedirect();
        $quote = Quote::latest('id')->firstOrFail();
        $item = $quote->items()->firstOrFail();
        $this->assertEqualsWithDelta(100, (float) $item->unit_price, 0.001);
        $this->assertTrue((bool) $quote->requires_approval);
        $this->assertSame('pending', $quote->approval_status);
    }

    public function test_commercial_discount_is_saved_as_pending_quote_not_approved(): void
    {
        [, $commercial, $client, $product] = $this->actorsAndProduct();
        $this->actingAs($commercial)->post(route('quotes.store'), $this->quotePayload($client, $product, 100, 15))->assertRedirect();
        $quote = Quote::latest('id')->firstOrFail();
        $item = $quote->items()->firstOrFail();
        $this->assertEqualsWithDelta(15, (float) $item->discount_percent, 0.001);
        $this->assertSame('pending', $quote->approval_status);
        $this->assertFalse($quote->canConvert());
    }

    public function test_admin_may_set_custom_price_without_separate_quote_approval(): void
    {
        [$admin, , $client, $product] = $this->actorsAndProduct();
        $this->actingAs($admin)->post(route('quotes.store'), $this->quotePayload($client, $product, 85, 5))->assertRedirect();
        $quote = Quote::latest('id')->firstOrFail();
        $item = $quote->items()->firstOrFail();
        $this->assertEqualsWithDelta(85, (float) $item->unit_price, 0.001);
        $this->assertEqualsWithDelta(5, (float) $item->discount_percent, 0.001);
        $this->assertFalse((bool) $quote->requires_approval);
    }

    public function test_commercial_cannot_update_catalog_product_price(): void
    {
        [, $commercial, , $product] = $this->actorsAndProduct();
        $this->actingAs($commercial)->put(route('products.update', $product), [
            'name' => 'Produit Pricing QA',
            'sku' => $product->sku,
            'unit' => 'pièce',
            'tax_rate' => 20,
            'sale_price_input' => 10,
            'purchase_price' => 5,
            'min_stock' => 1,
        ])->assertForbidden();
        $this->assertEqualsWithDelta(100, (float) $product->fresh()->sale_price, 0.001);
    }
}
