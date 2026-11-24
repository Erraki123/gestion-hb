<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceIssueApproval;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class HnbAdminInvoiceDirectIssueTest extends TestCase
{
    use RefreshDatabase;

    private function draft(): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $commercial = User::factory()->create(['role' => 'commercial', 'is_active' => true]);
        $client = Client::create(['name' => 'Client Facture QA', 'commercial_id' => $commercial->id, 'status' => 'actif']);
        $invoice = Invoice::create([
            'client_id' => $client->id, 'commercial_id' => $commercial->id,
            'type' => 'facture', 'status' => 'brouillon',
            'issue_date' => today(), 'due_date' => today()->addDays(30),
            'subtotal_ht' => 100, 'tax_rate' => 20, 'tax_amount' => 20,
            'total_ttc' => 120,
        ]);
        // Use the DB-hydrated representation, as route model binding does in production.
        return [$admin, $commercial, $invoice->fresh()];
    }

    public function test_admin_directly_issues_draft_and_logs_decision(): void
    {
        [$admin, $commercial, $invoice] = $this->draft();
        $this->actingAs($admin);
        app(InvoiceService::class)->issue($invoice, $admin->id);
        $this->assertSame('emise', $invoice->fresh()->status);
        $this->assertDatabaseHas('invoice_issue_approvals', [
            'invoice_id' => $invoice->id, 'status' => 'approved', 'decided_by' => $admin->id,
        ]);
    }

    public function test_admin_can_issue_pending_or_outdated_request_and_logs_decision(): void
    {
        [$admin, $commercial, $invoice] = $this->draft();
        $this->actingAs($commercial);
        app(InvoiceIssueApproval::class)->request($invoice, $commercial->id);
        $invoice->update(['notes' => 'Updated since approval request']);
        $this->actingAs($admin);
        app(InvoiceService::class)->issue($invoice, $admin->id);
        $row = DB::table('invoice_issue_approvals')->where('invoice_id', $invoice->id)->first();
        $this->assertSame('approved', $row->status);
        $this->assertSame($admin->id, (int) $row->decided_by);
        $this->assertSame($commercial->id, (int) $row->requested_by);
        $this->assertSame(app(InvoiceIssueApproval::class)->fingerprint($invoice->fresh()), $row->fingerprint);
    }

    public function test_valid_prior_director_approval_is_preserved(): void
    {
        [$admin, $commercial, $invoice] = $this->draft();
        $this->actingAs($commercial);
        app(InvoiceIssueApproval::class)->request($invoice, $commercial->id);
        $this->actingAs($admin);
        DB::table('invoice_issue_approvals')->where('invoice_id', $invoice->id)->update([
            'status' => 'approved', 'decided_by' => $admin->id, 'decided_at' => now(),
            'comment' => 'Original director comment',
        ]);
        $before = DB::table('invoice_issue_approvals')->where('invoice_id', $invoice->id)->first();
        $this->assertSame(app(InvoiceIssueApproval::class)->fingerprint($invoice->fresh()), $before->fingerprint);
        app(InvoiceService::class)->issue($invoice, $admin->id);
        $row = DB::table('invoice_issue_approvals')->where('invoice_id', $invoice->id)->first();
        $this->assertSame('Original director comment', $row->comment);
        $this->assertSame('emise', $invoice->fresh()->status);
    }

    public function test_commercial_cannot_directly_issue_invoice(): void
    {
        [$admin, $commercial, $invoice] = $this->draft();
        $this->actingAs($commercial);
        try {
            app(InvoiceService::class)->issue($invoice, $commercial->id);
            $this->fail('Commercial must not issue invoices directly.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->assertSame('brouillon', $invoice->fresh()->status);
        $this->assertDatabaseMissing('invoice_issue_approvals', ['invoice_id' => $invoice->id, 'status' => 'approved']);
    }
}
