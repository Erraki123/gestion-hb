<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProspectDetail;
use App\Models\Quote;
use App\Models\User;
use App\Services\HnbAgentRoutineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class HnbAgentRoutinesTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'commercial'): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function dueProspect(User $owner, string $company): ProspectDetail
    {
        $client = Client::create([
            'commercial_id' => $owner->id, 'name' => $company,
            'company' => $company, 'status' => 'prospect',
        ]);
        return ProspectDetail::create([
            'client_id' => $client->id, 'stage' => 'interesse',
            'category' => 'client_final', 'do_not_contact' => false,
            'next_follow_up_at' => now()->subDay(),
        ]);
    }

    public function test_guest_cannot_see_or_snooze(): void
    {
        $this->get(route('hnb.routines.index'))->assertRedirect();
        $this->post(route('hnb.routines.snooze'), [
            'key' => 'followup:1', 'fingerprint' => str_repeat('a', 64), 'hours' => 24,
        ])->assertRedirect();
        $this->assertDatabaseCount('hnb_agent_routine_snoozes', 0);
    }

    public function test_commercial_only_sees_own_due_prospects_and_opt_out_is_respected(): void
    {
        $a = $this->user(); $b = $this->user();
        $mine = $this->dueProspect($a, 'Prospect Mon Compte');
        $this->dueProspect($b, 'Prospect Autre Compte');
        $blocked = $this->dueProspect($a, 'Prospect Exclu');
        $blocked->update(['do_not_contact' => true]);
        $this->actingAs($a)->get(route('hnb.routines.index'))->assertOk()
            ->assertSee('Prospect Mon Compte')->assertDontSee('Prospect Autre Compte')
            ->assertDontSee('Prospect Exclu');
        $rows = app(HnbAgentRoutineService::class)->candidates($a);
        $this->assertArrayHasKey('followup:'.$mine->id, $rows);
        $this->assertCount(1, $rows);
    }

    public function test_director_reuses_existing_alerts_without_approving_or_sending(): void
    {
        $admin = $this->user('admin');
        $cat = Category::create(['name' => 'Test Actions '.uniqid()]);
        $product = Product::create([
            'category_id' => $cat->id, 'name' => 'Stock Phase 9', 'sku' => 'P9-'.uniqid(),
            'unit' => 'piece', 'purchase_price' => 25, 'sale_price' => 50,
            'stock_quantity' => 1, 'min_stock' => 3, 'is_active' => true,
        ]);
        Mail::fake(); Http::preventStrayRequests();
        $this->actingAs($admin)->get(route('hnb.routines.index'))
            ->assertOk()->assertSee('Stock à réapprovisionner')->assertSee('Stock Phase 9');
        $this->assertEquals(1, $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('hnb_agent_routine_snoozes', 0);
        $this->assertDatabaseCount('hnb_agent_routine_runs', 0);
        Mail::assertNothingSent(); Http::assertNothingSent();
    }

    public function test_snooze_is_per_user_and_does_not_modify_business_records(): void
    {
        $owner = $this->user(); $other = $this->user();
        $prospect = $this->dueProspect($owner, 'Suivi à appeler');
        $this->actingAs($owner);
        $key = 'followup:'.$prospect->id;
        $item = app(HnbAgentRoutineService::class)->candidates($owner)[$key];
        $this->post(route('hnb.routines.snooze'), [
            'key' => $key, 'fingerprint' => $item['fingerprint'], 'hours' => 24,
        ])->assertRedirect(route('hnb.routines.index'));
        $this->get(route('hnb.routines.index'))->assertOk()->assertDontSee('Suivi à appeler');
        $this->get(route('hnb.routines.index', ['show' => 'all']))->assertOk()->assertSee('Suivi à appeler');
        $this->assertTrue(app(HnbAgentRoutineService::class)->withSnoozes($owner)[$key]['snoozed']);
        $this->assertFalse(app(HnbAgentRoutineService::class)->snooze($other, $key, $item['fingerprint'], 24));
        $this->assertSame('interesse', $prospect->fresh()->stage);
        $this->post(route('hnb.routines.snooze'), [
            'key' => $key, 'fingerprint' => $item['fingerprint'], 'hours' => 0,
        ])->assertRedirect();
        $this->assertFalse(app(HnbAgentRoutineService::class)->withSnoozes($owner)[$key]['snoozed']);
    }

    public function test_stale_fingerprint_is_rejected_and_change_reactivates_reminder(): void
    {
        $owner = $this->user();
        $p = $this->dueProspect($owner, 'Échéance ancienne');
        $this->actingAs($owner);
        $key = 'followup:'.$p->id;
        $old = app(HnbAgentRoutineService::class)->candidates($owner)[$key];
        $this->post(route('hnb.routines.snooze'), [
            'key' => $key, 'fingerprint' => $old['fingerprint'], 'hours' => 72,
        ])->assertRedirect();
        $p->update(['next_follow_up_at' => now()->subHours(5)]);
        $this->post(route('hnb.routines.snooze'), [
            'key' => $key, 'fingerprint' => $old['fingerprint'], 'hours' => 24,
        ])->assertStatus(409);
        $this->assertFalse(app(HnbAgentRoutineService::class)->withSnoozes($owner)[$key]['snoozed']);
        $this->post(route('hnb.routines.snooze'), [
            'key' => '../../secret', 'fingerprint' => 'x', 'hours' => 720,
        ])->assertSessionHasErrors(['key', 'fingerprint', 'hours']);
    }

    public function test_daily_scan_is_idempotent_and_dry_run_never_writes_or_sends(): void
    {
        $admin = $this->user('admin'); $commercial = $this->user();
        $this->dueProspect($commercial, 'Prospect routine');
        Mail::fake(); Http::preventStrayRequests();
        $this->artisan('hnb:routines:refresh', ['--dry-run' => true])->assertExitCode(0);
        $this->assertDatabaseCount('hnb_agent_routine_runs', 0);
        $this->artisan('hnb:routines:refresh')->assertExitCode(0);
        $this->artisan('hnb:routines:refresh')->assertExitCode(0);
        $this->assertDatabaseCount('hnb_agent_routine_runs', 2);
        $this->assertDatabaseHas('hnb_agent_routine_runs', [
            'user_id' => $commercial->id, 'total_tasks' => 1,
        ]);
        Mail::assertNothingSent(); Http::assertNothingSent();
    }

    public function test_floating_assistant_returns_owner_scoped_actions_without_network(): void
    {
        $a = $this->user(); $b = $this->user();
        $this->dueProspect($a, 'Action Personnel');
        $this->dueProspect($b, 'Action Privée Autre');
        Mail::fake(); Http::preventStrayRequests();
        $this->actingAs($a)->postJson(route('admin.ai-floating.ask'), [
            'question' => 'Quelles sont mes tâches aujourd’hui ?',
        ])->assertOk()->assertJsonPath('error', null)
            ->assertSee('Action Personnel')->assertDontSee('Action Privée Autre');
        Mail::assertNothingSent(); Http::assertNothingSent();
    }

    public function test_paid_or_resolved_records_disappear_and_scope_is_checked_again(): void
    {
        $commercial = $this->user();
        $client = Client::create(['commercial_id'=>$commercial->id, 'name'=>'Entreprise paiement', 'status'=>'actif']);
        $invoice = Invoice::create([
            'number' => 'FAC-AGENT-'.uniqid(), 'type' => 'facture',
            'client_id' => $client->id, 'commercial_id' => $commercial->id,
            'status' => 'emise', 'issue_date' => today()->subDays(10), 'due_date' => today()->subDays(4),
            'total_ttc' => 1000, 'amount_paid' => 0, 'amount_cheque_pending' => 0,
        ]);
        $agent = app(HnbAgentRoutineService::class);
        $this->actingAs($commercial);
        $key = 'overdue:'.$invoice->id;
        $this->assertArrayHasKey($key, $agent->candidates($commercial));
        $invoice->update(['amount_paid' => 1000, 'status' => 'payee']);
        $this->assertArrayNotHasKey($key, $agent->candidates($commercial));
    }
}
