<?php
namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HnbMarketWatchTest extends TestCase
{
    use RefreshDatabase;
    private function actor(string $role): User { return User::factory()->create(['role'=>$role,'is_active'=>true]); }
    private function product(): Product { return Product::create(['name'=>'Lecteur QA','sku'=>'QA-F18','unit'=>'pièce','sale_price'=>1000,'tax_rate'=>20,'is_active'=>true]); }
    private function payload(array $extra=[]): array
    {
        return array_merge(['company'=>'Vendeur QA','city'=>'Casablanca','sku'=>'QA-F18','basis'=>'TTC','public_price'=>'1100.00','source_url'=>'https://example.test/offer','observed_on'=>today()->toDateString(),'same_reference'=>'1','same_unit'=>'1','source_checked'=>'1'],$extra);
    }
    public function test_guest_and_commercial_cannot_access_market_watch(): void
    {
        Http::fake();
        $this->get(route('admin.market-watch.index'))->assertRedirect(route('login'));
        $this->actingAs($this->actor('commercial'))->get(route('admin.market-watch.index'))->assertForbidden();
        $this->post(route('admin.market-watch.search'),['city'=>'Casablanca'])->assertForbidden();
        $this->post(route('admin.market-watch.store'),$this->payload())->assertForbidden();
        Http::assertNothingSent();$this->assertDatabaseCount('hnb_market_observations',0);
    }
    public function test_public_search_is_preview_only_with_source(): void
    {
        $this->withoutVite();$this->actingAs($this->actor('admin'));
        config(['prospect_discovery.enabled'=>true,'prospect_discovery.osm_enabled'=>true,'prospect_discovery.overpass_url'=>'https://overpass.example.test/api']);
        Http::preventStrayRequests();Http::fake(['https://overpass.example.test/api'=>Http::response(['elements'=>[['type'=>'node','id'=>123,'tags'=>['name'=>'Security QA']]]])]);
        $this->post(route('admin.market-watch.search'),['city'=>'Casablanca'])->assertOk()->assertSee('Security QA')->assertSee('https://www.openstreetmap.org/node/123')->assertSee('Activité à vérifier');
        $this->assertDatabaseCount('discovered_prospects',0);$this->assertDatabaseCount('clients',0);$this->assertDatabaseCount('hnb_market_observations',0);Http::assertSentCount(1);
    }
    public function test_disabled_source_or_unknown_city_never_requests_network(): void
    {
        $this->withoutVite();$this->actingAs($this->actor('admin'));Http::fake();config(['prospect_discovery.enabled'=>false]);
        $this->post(route('admin.market-watch.search'),['city'=>'Casablanca'])->assertOk()->assertSee('Discovery disabled');
        $this->post(route('admin.market-watch.search'),['city'=>'unsafe'])->assertSessionHasErrors('city');Http::assertNothingSent();
    }
    public function test_observation_keeps_catalog_snapshot_and_no_price_change(): void
    {
        $this->withoutVite();$admin=$this->actor('admin');$this->actingAs($admin);$product=$this->product();Http::fake();
        $this->post(route('admin.market-watch.store'),$this->payload())->assertRedirect(route('admin.market-watch.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('hnb_market_observations',['sku'=>'QA-F18','basis'=>'TTC','catalog_price'=>1200,'public_price'=>1100,'product_id'=>$product->id,'user_id'=>$admin->id,'source_url'=>'https://example.test/offer']);
        $this->assertEquals(1000,(float)$product->fresh()->sale_price);Http::assertNothingSent();
        $product->update(['sale_price'=>2000]);
        $this->assertEquals(1200,(float)DB::table('hnb_market_observations')->value('catalog_price'));
        $this->get(route('admin.market-watch.index'))->assertOk()->assertSee('QA-F18')->assertSee('100,00 DH');
    }
    public function test_invalid_price_source_and_unconfirmed_comparison_are_rejected(): void
    {
        $this->actingAs($this->actor('admin'));$this->product();Http::fake();
        $this->post(route('admin.market-watch.store'),$this->payload(['same_reference'=>'0','source_url'=>'http://example.test/offer','public_price'=>'-5']))->assertSessionHasErrors(['same_reference','source_url','public_price']);
        $this->post(route('admin.market-watch.store'),$this->payload(['sku'=>'MISSING']))->assertSessionHasErrors('sku');
        $this->assertDatabaseCount('hnb_market_observations',0);Http::assertNothingSent();
    }
}
