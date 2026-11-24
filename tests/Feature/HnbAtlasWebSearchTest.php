<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HnbAtlasWebSearchTest extends TestCase
{
    use RefreshDatabase;
    private function user(string $role): User { return User::factory()->create(['role'=>$role,'is_active'=>true]); }
    private function source(): void
    {
        config(['prospect_discovery.enabled'=>true,'prospect_discovery.osm_enabled'=>true,'prospect_discovery.overpass_url'=>'https://overpass.example.test/api']);
        Http::preventStrayRequests();
        Http::fake(['https://overpass.example.test/api'=>Http::response(['elements'=>[['type'=>'node','id'=>999,'tags'=>['name'=>'Hotel QA','tourism'=>'hotel','phone'=>'+212612345678']]]])]);
    }
    private function payload(string $mode='preview'): array { return ['city'=>'Casablanca','sector'=>'hotellerie','mode'=>$mode]; }
    public function test_guest_cannot_search(): void
    {
        Http::fake(); $this->get(route('prospects.atlas.index'))->assertRedirect(route('login'));
        $this->post(route('prospects.atlas.search'),$this->payload())->assertRedirect(route('login')); Http::assertNothingSent();
    }
    public function test_commercial_preview_does_not_write_or_expose_review_counts(): void
    {
        $this->withoutVite(); $this->source(); $this->actingAs($this->user('commercial'));
        $this->post(route('prospects.atlas.search'),$this->payload())->assertOk()->assertSee('Hotel QA')->assertSee('Complétude')->assertDontSee('Nouvelles fiches potentielles');
        $this->assertDatabaseCount('discovered_prospects',0);$this->assertDatabaseCount('clients',0);Http::assertSentCount(1);
    }
    public function test_commercial_cannot_save_results(): void
    {
        $this->source(); $this->actingAs($this->user('commercial'))->post(route('prospects.atlas.search'),$this->payload('review'))->assertForbidden();
        Http::assertNothingSent();$this->assertDatabaseCount('discovered_prospects',0);
    }
    public function test_admin_saves_pending_suggestions_only_and_deduplicates(): void
    {
        $this->withoutVite();$this->source();$this->actingAs($this->user('admin'));
        $this->post(route('prospects.atlas.search'),$this->payload('review'))->assertOk()->assertSee('Hotel QA');
        $this->post(route('prospects.atlas.search'),$this->payload('review'))->assertOk();
        $this->assertDatabaseCount('discovered_prospects',1);$this->assertDatabaseHas('discovered_prospects',['source_id'=>'node:999','status'=>'pending']);$this->assertDatabaseCount('clients',0);
    }
    public function test_disabled_source_and_invalid_sector_make_no_external_calls(): void
    {
        $this->withoutVite();$this->source();config(['prospect_discovery.osm_enabled'=>false]);$this->actingAs($this->user('admin'));
        $this->post(route('prospects.atlas.search'),$this->payload())->assertOk()->assertSee('OSM disabled');
        $this->post(route('prospects.atlas.search'),array_merge($this->payload(),['sector'=>'unsafe']))->assertSessionHasErrors('sector');Http::assertNothingSent();
    }
}
