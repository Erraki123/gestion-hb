<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use App\Services\DocumentReleaseApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HnBloomDirectAdminReleaseTest extends TestCase
{
    use RefreshDatabase;

    private function quoteFixture(): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $commercial = User::factory()->create(['role' => 'commercial', 'is_active' => true]);
        $client = Client::create([
            'commercial_id' => $commercial->id, 'name' => 'Client QA', 'status' => 'actif',
            'email' => 'client@example.test',
        ]);
        $quote = Quote::create([
            'number' => 'QA-DIRECT-001', 'client_id' => $client->id,
            'commercial_id' => $commercial->id, 'status' => 'brouillon',
            'issue_date' => now()->toDateString(), 'total_ttc' => 120,
            'requires_approval' => true, 'approval_status' => 'pending',
        ]);
        return [$admin, $commercial, $client, $quote];
    }

    public function test_commercial_cannot_use_direct_send_action(): void
    {
        [$admin, $commercial, $client, $quote] = $this->quoteFixture();
        $this->actingAs($commercial)
            ->post(route('documents.send', ['type' => 'quote', 'id' => $quote->id]))
            ->assertForbidden();
        $this->assertDatabaseMissing('document_release_approvals', [
            'document_type' => 'quote', 'document_id' => $quote->id,
        ]);
    }

    public function test_admin_click_records_an_approved_version_even_if_delivery_channels_are_disabled(): void
    {
        [$admin, $commercial, $client, $quote] = $this->quoteFixture();
        config(['notify.email.enabled' => false]);
        $this->actingAs($admin)
            ->post(route('documents.send', ['type' => 'quote', 'id' => $quote->id]))
            ->assertSessionHasErrors('send'); // Nothing enabled to send; approval still recorded.
        $this->assertSame('approved', $quote->fresh()->approval_status);
        $this->assertDatabaseHas('document_release_approvals', [
            'document_type' => 'quote', 'document_id' => $quote->id,
            'status' => 'approved', 'requested_by' => $admin->id, 'decided_by' => $admin->id,
        ]);
        $this->assertTrue(app(DocumentReleaseApproval::class)->canRelease($quote->fresh()));
    }

    public function test_change_of_recipient_invalidates_direct_admin_release(): void
    {
        [$admin, $commercial, $client, $quote] = $this->quoteFixture();
        app(DocumentReleaseApproval::class)->approveForAdminSend($quote, $admin->id);
        $this->assertTrue(app(DocumentReleaseApproval::class)->canRelease($quote));
        $client->update(['email' => 'changed@example.test']);
        $this->assertFalse(app(DocumentReleaseApproval::class)->canRelease($quote->fresh()));
    }
}
