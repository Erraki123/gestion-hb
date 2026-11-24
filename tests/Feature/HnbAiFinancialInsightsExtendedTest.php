<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HnbAiFinancialInsightsExtendedTest extends TestCase
{
    use RefreshDatabase;

    private function setupActors(): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $a = User::factory()->create(['role' => 'commercial', 'is_active' => true]);
        $b = User::factory()->create(['role' => 'commercial', 'is_active' => true]);
        $clientA = Client::create(['commercial_id' => $a->id, 'name' => 'Client A', 'status' => 'actif']);
        $clientB = Client::create(['commercial_id' => $b->id, 'name' => 'Client B', 'status' => 'actif']);
        return [$admin, $a, $b, $clientA, $clientB];
    }

    private function invoice(Client $client, User $seller, string $status = 'emise'): Invoice
    {
        return Invoice::create([
            'type' => 'facture', 'client_id' => $client->id, 'commercial_id' => $seller->id,
            'status' => $status, 'issue_date' => today()->toDateString(),
            'due_date' => today()->addDays(14)->toDateString(),
            'total_ttc' => 200, 'amount_paid' => 0,
        ]);
    }

    private function pay(Invoice $invoice, User $actor, float $amount, string $method, ?string $chequeStatus = null, string $status = 'valide'): void
    {
        Payment::create([
            'number' => 'PAY-TEST-'.uniqid(), 'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id, 'commercial_id' => $invoice->commercial_id,
            'user_id' => $actor->id, 'paid_at' => today()->toDateString(),
            'status' => $status, 'amount' => $amount, 'method' => $method,
            'cheque_status' => $chequeStatus,
        ]);
    }

    private function answer(User $user, string $question): string
    {
        return (string) $this->actingAs($user)
            ->postJson(route('admin.ai-floating.ask'), ['question' => $question])
            ->assertOk()->json('answer');
    }

    public function test_encaissements_separate_pending_cheques_and_exclude_cancelled_payments(): void
    {
        [$admin, $a, , $clientA] = $this->setupActors();
        $inv = $this->invoice($clientA, $a);
        $this->pay($inv, $admin, 80, 'virement');
        $this->pay($inv, $admin, 40, 'cheque', 'en_attente');
        $this->pay($inv, $admin, 20, 'especes', null, 'annule');
        $text = $this->answer($admin, 'encaissements ce mois');
        $this->assertStringContainsString('120,00 MAD', $text);
        $this->assertStringContainsString('40,00 MAD', $text);
        $this->assertStringContainsString('80,00 MAD', $text);
        $this->assertStringNotContainsString('140,00 MAD', $text);
    }

    public function test_encaissements_respect_commercial_visibility(): void
    {
        [$admin, $a, $b, $clientA, $clientB] = $this->setupActors();
        $this->pay($this->invoice($clientA, $a), $admin, 60, 'especes');
        $this->pay($this->invoice($clientB, $b), $admin, 700, 'virement');
        $commercial = $this->answer($a, 'encaissements ce mois');
        $this->assertStringContainsString('60,00 MAD', $commercial);
        $this->assertStringNotContainsString('760,00 MAD', $commercial);
        $this->assertStringContainsString('760,00 MAD', $this->answer($admin, 'encaissements ce mois'));
    }

    public function test_top_products_count_only_issued_invoices_visible_to_user(): void
    {
        [$admin, $a, $b, $clientA, $clientB] = $this->setupActors();
        $pa = Product::create(['name' => 'Camera Unique', 'sku' => 'SKU-CAMERA-01', 'is_active' => true]);
        $pb = Product::create(['name' => 'NVR Secret', 'sku' => 'SKU-NVR-02', 'is_active' => true]);
        $invA = $this->invoice($clientA, $a);
        $invB = $this->invoice($clientB, $b);
        $draft = $this->invoice($clientA, $a, 'brouillon');
        foreach ([[$invA, $pa, 3], [$invB, $pb, 8], [$draft, $pb, 99]] as [$inv, $product, $qty]) {
            InvoiceItem::create([
                'invoice_id' => $inv->id, 'product_id' => $product->id,
                'name' => $product->name, 'sku' => $product->sku, 'unit' => 'unité',
                'quantity' => $qty, 'unit_price' => 20, 'line_total_ht' => $qty * 20,
                'discount_percent' => 0,
            ]);
        }
        $commercial = $this->answer($a, 'top produits ce mois');
        $this->assertStringContainsString('Camera Unique', $commercial);
        $this->assertStringNotContainsString('NVR Secret', $commercial);
        $director = $this->answer($admin, 'top produits ce mois');
        $this->assertStringContainsString('NVR Secret', $director);
        $this->assertStringContainsString('Camera Unique', $director);
        $this->assertStringNotContainsString('99,00', $director);
    }

    public function test_read_only_dangerous_action_is_not_treated_as_financial_query(): void
    {
        [$admin] = $this->setupActors();
        $answer = $this->answer($admin, 'supprime les paiements ce mois');
        $this->assertStringNotContainsString('Paiements enregistrés ce mois', $answer);
    }
}
