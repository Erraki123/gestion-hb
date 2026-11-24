<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HnbAiFinancialInsightsTest extends TestCase
{
    use RefreshDatabase;

    private function actors(): array
    {
        $admin = User::factory()->create(['role'=>'admin','is_active'=>true]);
        $a = User::factory()->create(['role'=>'commercial','is_active'=>true]);
        $b = User::factory()->create(['role'=>'commercial','is_active'=>true]);
        $clientA = Client::create(['commercial_id'=>$a->id,'name'=>'Test Client A','status'=>'actif']);
        $clientB = Client::create(['commercial_id'=>$b->id,'name'=>'Test Client B','status'=>'actif']);
        return [$admin,$a,$b,$clientA,$clientB];
    }

    private function invoice(Client $client, User $commercial, float $amount, array $changes=[]): Invoice
    {
        return Invoice::create(array_merge([
            'client_id'=>$client->id,
            'commercial_id'=>$commercial->id,
            'type'=>'facture',
            'status'=>'emise',
            'issue_date'=>today()->toDateString(),
            'due_date'=>today()->addDays(15)->toDateString(),
            'total_ttc'=>$amount,
            'amount_paid'=>0,
        ], $changes));
    }

    private function ask(User $user, string $question): string
    {
        return (string) $this->actingAs($user)->postJson(route('admin.ai-floating.ask'), ['question'=>$question])
            ->assertOk()->json('answer');
    }

    public function test_monthly_sales_do_not_leak_other_commercial_invoices(): void
    {
        [$admin,$a,$b,$clientA,$clientB]=$this->actors();
        $this->invoice($clientA,$a,123);
        $this->invoice($clientB,$b,456);
        $this->assertStringContainsString('123,00 MAD', $this->ask($a,'ventes ce mois'));
        $this->assertStringNotContainsString('579,00 MAD', $this->ask($a,'ventes ce mois'));
        $this->assertStringContainsString('579,00 MAD', $this->ask($admin,'ventes ce mois'));
    }

    public function test_monthly_sales_deduct_issued_credit_notes_and_exclude_drafts(): void
    {
        [$admin,$a,,$clientA]=$this->actors();
        $parent=$this->invoice($clientA,$a,200);
        $this->invoice($clientA,$a,999,['status'=>'brouillon']);
        $this->invoice($clientA,$a,50,['type'=>'avoir','parent_id'=>$parent->id]);
        $answer=$this->ask($admin,'ventes ce mois');
        $this->assertStringContainsString('150,00 MAD',$answer);
        $this->assertStringNotContainsString('1 149,00 MAD',$answer);
    }

    public function test_outstanding_balances_deduct_payments_and_credits_and_respect_ownership(): void
    {
        [$admin,$a,$b,$clientA,$clientB]=$this->actors();
        $parent=$this->invoice($clientA,$a,200,['amount_paid'=>30,'due_date'=>today()->subDay()->toDateString()]);
        $this->invoice($clientA,$a,50,['type'=>'avoir','parent_id'=>$parent->id]);
        $this->invoice($clientB,$b,500);
        $commercialAnswer=$this->ask($a,'factures impayées');
        $this->assertStringContainsString('120,00 MAD', $commercialAnswer);
        $this->assertStringNotContainsString('620,00 MAD', $commercialAnswer);
        $this->assertStringContainsString('620,00 MAD', $this->ask($admin,'factures impayées'));
    }

    public function test_low_stock_is_counted_and_preview_is_explicit(): void
    {
        [$admin]=$this->actors();
        Product::create(['name'=>'Caméra test','sku'=>'HNB-LOW-001','stock_quantity'=>2,'min_stock'=>5,'is_active'=>true]);
        Product::create(['name'=>'Stock ok','sku'=>'HNB-OK-002','stock_quantity'=>12,'min_stock'=>5,'is_active'=>true]);
        $answer=$this->ask($admin,'stock bas');
        $this->assertStringContainsString('Produits actifs au seuil ou en dessous : 1', $answer);
        $this->assertStringContainsString('Caméra test', $answer);
        $this->assertStringNotContainsString('Stock ok', $answer);
    }
}
