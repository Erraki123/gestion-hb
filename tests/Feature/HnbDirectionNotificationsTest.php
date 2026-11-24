<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\DiscoveredProspect;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Services\HnbDirectionNotificationCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class HnbDirectionNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function product(): Product
    {
        $category = Category::create(['name' => 'Test notifications '.uniqid()]);
        return Product::create([
            'category_id' => $category->id, 'name' => 'F18 Test', 'sku' => 'F18-'.uniqid(),
            'unit' => 'piece', 'purchase_price' => 100, 'sale_price' => 150,
            'stock_quantity' => 1, 'min_stock' => 5, 'is_active' => true,
        ]);
    }

    public function test_only_admin_can_access_or_acknowledge(): void
    {
        $this->get(route('admin.direction.notifications.index'))->assertRedirect();
        $this->post(route('admin.direction.notifications.seen'), ['key' => 'stock:1', 'fingerprint' => str_repeat('a', 64)])->assertRedirect();
        $commercial = User::factory()->create(['role' => 'commercial', 'is_active' => true]);
        $this->actingAs($commercial)->get(route('admin.direction.notifications.index'))->assertForbidden();
        $this->actingAs($commercial)->post(route('admin.direction.notifications.seen'), [
            'key' => 'stock:1', 'fingerprint' => str_repeat('a', 64),
        ])->assertForbidden();
    }

    public function test_admin_sees_real_stock_and_atlas_alerts_without_sending(): void
    {
        $admin = $this->admin();
        $p = $this->product();
        DiscoveredProspect::create([
            'source' => 'osm', 'source_id' => 'unit:1', 'company' => 'Clinique Exemple',
            'status' => 'pending', 'city' => 'Casablanca', 'category' => 'sante',
        ]);
        Mail::fake();
        $this->actingAs($admin)->get(route('admin.direction.notifications.index'))
            ->assertOk()->assertSee('Centre de notifications')
            ->assertSee('Stock à réapprovisionner')->assertSee($p->name)
            ->assertSee('ATLAS : suggestion à examiner')->assertSee('Clinique Exemple');
        $this->assertDatabaseCount('hnb_direction_alert_reads', 0);
        Mail::assertNothingSent();
    }

    public function test_mark_seen_does_not_approve_and_is_per_admin(): void
    {
        $first = $this->admin();
        $second = $this->admin();
        $p = $this->product();
        $client = Client::create(['commercial_id' => $first->id, 'name' => 'Entreprise A', 'status' => 'actif']);
        $q = Quote::create([
            'number' => 'DV-ALERT-'.uniqid(), 'client_id' => $client->id, 'commercial_id' => $first->id,
            'status' => 'brouillon', 'issue_date' => today(), 'total_ttc' => 120,
            'requires_approval' => true, 'approval_status' => 'pending', 'approval_requested_at' => now(),
        ]);
        $this->actingAs($first);
        $alerts = app(HnbDirectionNotificationCenter::class)->withSeen($first->id);
        $alert = $alerts['quote:'.$q->id];
        $this->post(route('admin.direction.notifications.seen'), [
            'key' => $alert['key'], 'fingerprint' => $alert['fingerprint'],
        ])->assertRedirect(route('admin.direction.notifications.index'));
        $this->assertTrue(app(HnbDirectionNotificationCenter::class)->withSeen($first->id)['quote:'.$q->id]['seen']);
        $this->assertFalse(app(HnbDirectionNotificationCenter::class)->withSeen($second->id)['quote:'.$q->id]['seen']);
        $this->assertSame('pending', $q->fresh()->approval_status);
        $this->assertSame(1, DB::table('hnb_direction_alert_reads')->count());
        $this->post(route('admin.direction.notifications.seen'), [
            'key' => $alert['key'], 'fingerprint' => $alert['fingerprint'],
        ])->assertRedirect();
        $this->assertSame(1, DB::table('hnb_direction_alert_reads')->count());
    }

    public function test_resolved_alert_disappears_and_stale_fingerprint_is_rejected(): void
    {
        $admin = $this->admin();
        $p = $this->product();
        $this->actingAs($admin);
        $key = 'stock:'.$p->id;
        $old = app(HnbDirectionNotificationCenter::class)->withSeen($admin->id)[$key];
        $p->update(['stock_quantity' => 2]);
        $this->post(route('admin.direction.notifications.seen'), [
            'key' => $old['key'], 'fingerprint' => $old['fingerprint'],
        ])->assertStatus(409);
        $this->assertDatabaseCount('hnb_direction_alert_reads', 0);
        $p->update(['stock_quantity' => 20]);
        $this->assertArrayNotHasKey($key, app(HnbDirectionNotificationCenter::class)->active());
    }

    public function test_resolved_approval_no_longer_alerts(): void
    {
        $admin = $this->admin();
        $client = Client::create(['commercial_id' => $admin->id, 'name' => 'Client', 'status' => 'actif']);
        $q = Quote::create([
            'number' => 'DV-NOTIF-'.uniqid(), 'client_id' => $client->id, 'commercial_id' => $admin->id,
            'status' => 'brouillon', 'issue_date' => today(), 'total_ttc' => 120,
            'requires_approval' => true, 'approval_status' => 'pending', 'approval_requested_at' => now(),
        ]);
        $this->actingAs($admin);
        $this->assertArrayHasKey('quote:'.$q->id, app(HnbDirectionNotificationCenter::class)->active());
        $q->update(['approval_status' => 'approved']);
        $this->assertArrayNotHasKey('quote:'.$q->id, app(HnbDirectionNotificationCenter::class)->active());
    }

    public function test_validation_rejects_fake_keys_and_tokens(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.direction.notifications.seen'), [
            'key' => '../../orders/1', 'fingerprint' => 'bad',
        ])->assertSessionHasErrors(['key', 'fingerprint']);
        $this->post(route('admin.direction.notifications.seen'), [
            'key' => 'quote:999999', 'fingerprint' => str_repeat('0', 64),
        ])->assertStatus(409);
        $this->assertDatabaseCount('hnb_direction_alert_reads', 0);
    }
}
