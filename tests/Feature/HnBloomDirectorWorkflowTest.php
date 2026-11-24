<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HnBloomDirectorWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function actors(): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $commercial = User::factory()->create(['role' => 'commercial', 'is_active' => true]);
        $client = Client::create([
            'commercial_id' => $commercial->id,
            'name' => 'Client QA Local',
            'status' => 'actif',
        ]);
        return [$admin, $commercial, $client];
    }

    private function payload(Client $client, float $price = 100): array
    {
        return [
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(10)->toDateString(),
            'items' => [[
                'name' => 'Service QA Test', 'unit' => 'forfait',
                'quantity' => 1, 'unit_price' => $price,
                'discount_percent' => 0, 'tax_rate' => 20,
            ]],
        ];
    }

    private function createQuote(User $commercial, Client $client): Quote
    {
        $this->actingAs($commercial)->post(route('quotes.store'), $this->payload($client))->assertRedirect();
        $quote = Quote::latest('id')->first();
        $this->assertNotNull($quote, 'Quote creation failed.');
        $this->assertTrue((bool) $quote->requires_approval);
        $this->assertSame('pending', $quote->approval_status);
        return $quote;
    }

    public function test_every_new_quote_requires_director_approval(): void
    {
        [$admin, $commercial, $client] = $this->actors();
        $quote = $this->createQuote($commercial, $client);
        $this->assertFalse($quote->canConvert());
        $this->actingAs($commercial)->post(route('quotes.convert', $quote))->assertSessionHasErrors('approval');
        $this->assertDatabaseMissing('orders', ['quote_id' => $quote->id]);
    }

    public function test_commercial_cannot_decide_and_admin_can_approve(): void
    {
        [$admin, $commercial, $client] = $this->actors();
        $quote = $this->createQuote($commercial, $client);
        $route = route('quotes.approval.decide', $quote);
        $this->actingAs($commercial)->post($route, ['decision' => 'approved'])->assertForbidden();
        $this->assertSame('pending', $quote->fresh()->approval_status);
        $this->actingAs($admin)->post($route, ['decision' => 'approved'])->assertRedirect();
        $this->assertSame('approved', $quote->fresh()->approval_status);
        $this->assertTrue($quote->fresh()->canConvert());
    }

    public function test_rejection_blocks_conversion(): void
    {
        [$admin, $commercial, $client] = $this->actors();
        $quote = $this->createQuote($commercial, $client);
        $this->actingAs($admin)->post(route('quotes.approval.decide', $quote), [
            'decision' => 'rejected', 'comment' => 'Corriger la quantite',
        ])->assertRedirect();
        $this->assertSame('rejected', $quote->fresh()->approval_status);
        $this->actingAs($commercial)->post(route('quotes.convert', $quote))->assertSessionHasErrors('approval');
        $this->assertDatabaseMissing('orders', ['quote_id' => $quote->id]);
    }

    public function test_changing_approved_quote_resets_approval(): void
    {
        [$admin, $commercial, $client] = $this->actors();
        $quote = $this->createQuote($commercial, $client);
        $this->actingAs($admin)->post(route('quotes.approval.decide', $quote), [
            'decision' => 'approved',
        ])->assertRedirect();
        $this->actingAs($commercial)->put(route('quotes.update', $quote), $this->payload($client, 175))->assertRedirect();
        $this->assertSame('pending', $quote->fresh()->approval_status);
        $this->assertFalse($quote->fresh()->canConvert());
    }
}
