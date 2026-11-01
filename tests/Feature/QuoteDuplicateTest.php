<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_can_be_duplicated(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $client = Client::create([
            'commercial_id' => $admin->id,
            'name' => 'Client Test',
            'status' => 'actif',
        ]);

        $category = Category::create(['name' => 'Catégorie test']);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Produit test',
            'sku' => 'SKU-1',
            'unit' => 'pièce',
            'purchase_price' => 30,
            'sale_price' => 50,
            'stock_quantity' => 10,
            'min_stock' => 1,
            'is_active' => true,
        ]);

        $quote = Quote::create([
            'number' => 'DV-001',
            'client_id' => $client->id,
            'commercial_id' => $admin->id,
            'status' => 'brouillon',
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(10)->toDateString(),
            'tax_rate' => 20,
            'subtotal_ht' => 100,
            'tax_amount' => 20,
            'total_ttc' => 120,
        ]);

        QuoteItem::create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'name' => 'Produit test',
            'sku' => 'SKU-1',
            'unit' => 'pièce',
            'quantity' => 2,
            'unit_price' => 50,
            'discount_percent' => 0,
            'line_total_ht' => 100,
        ]);

        $response = $this->actingAs($admin)->post("/quotes/{$quote->id}/duplicate");

        $response->assertRedirect();
        $this->assertDatabaseCount('quotes', 2);
    }
}
