<?php
namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HnbMarketResearchTest extends TestCase
{
    use RefreshDatabase;
    private function loginAs(string $role='admin'): void { $this->withoutVite(); $this->actingAs(User::factory()->create(['role'=>$role,'is_active'=>true])); }
    private function product(): Product { return Product::create(['name'=>'Lecteur QA','sku'=>'QA-F18','unit'=>'pièce','sale_price'=>1000,'tax_rate'=>20,'is_active'=>true]); }
    private function sources(): void
    {
        config(['hnb_market_research.sources'=>['vendor_a'=>['url'=>'https://feed-a.example.test/offers','offer_hosts'=>['shop-a.example.test']], 'vendor_b'=>['url'=>'https://feed-b.example.test/offers','offer_hosts'=>['shop-b.example.test']]]]);
    }
    private function offer(array $replace=[]): array { return array_replace(['sku'=>'QA-F18','company'=>'Public QA','unit'=>'pièce','currency'=>'MAD','basis'=>'TTC','public_price'=>'1100.00','observed_on'=>today()->toDateString(),'source_url'=>'https://shop-a.example.test/offer'], $replace); }
    private function observation(Product $p): void
    {
        DB::table('hnb_market_observations')->insert(['product_id'=>$p->id,'user_id'=>auth()->id(),'company'=>'Public QA','city'=>'Casablanca','sku'=>$p->sku,'product_name'=>$p->name,'unit'=>$p->unit,'basis'=>'TTC','public_price'=>1100,'catalog_price'=>1234.56,'source_url'=>'https://shop-a.example.test/offer','observed_on'=>today()->toDateString(),'created_at'=>now(),'updated_at'=>now()]);
    }
    public function test_guest_and_commercial_cannot_research_or_report(): void
    {
        Http::fake(); $this->get(route('admin.market-research.index'))->assertRedirect(route('login'));
        $this->loginAs('commercial');
        $this->get(route('admin.market-research.index'))->assertForbidden();
        $this->post(route('admin.market-research.preview'),[])->assertForbidden();
        $this->post(route('admin.market-research.report'),[])->assertForbidden(); Http::assertNothingSent();
    }
    public function test_two_approved_feeds_compare_exact_reference_without_writes(): void
    {
        $this->loginAs();$p=$this->product();$this->sources(); Http::preventStrayRequests();
        Http::fake(['https://feed-a.example.test/offers'=>Http::response(['offers'=>[$this->offer(),$this->offer()]]),'https://feed-b.example.test/offers'=>Http::response(['offers'=>[$this->offer(['company'=>'Second QA','public_price'=>'1300.00','source_url'=>'https://shop-b.example.test/offer'])]])]);
        $r=$this->post(route('admin.market-research.preview'),['sku'=>$p->sku,'sources'=>['vendor_a','vendor_b']]);
        $r->assertOk()->assertSee('Public QA')->assertSee('Second QA')->assertSee('100,00 MAD')->assertSee('https://shop-b.example.test/offer');
        $this->assertCount(2,$r->viewData('rows'));Http::assertSentCount(2);
        $this->assertDatabaseCount('hnb_market_observations',0);$this->assertEquals(1000,(float)$p->fresh()->sale_price);
    }
    public function test_invalid_offers_and_unapproved_source_are_not_used(): void
    {
        $this->loginAs();$p=$this->product();$this->sources();Http::preventStrayRequests();
        Http::fake(['https://feed-a.example.test/offers'=>Http::response(['offers'=>[$this->offer(['sku'=>'OTHER']),$this->offer(['currency'=>'EUR']),$this->offer(['basis'=>'unknown']),$this->offer(['unit'=>'lot']),$this->offer(['source_url'=>'https://other.example.test/item']),$this->offer(['observed_on'=>'2099-01-01']),$this->offer(['public_price'=>'0'])]])]);
        $r=$this->post(route('admin.market-research.preview'),['sku'=>$p->sku,'sources'=>['vendor_a']]);$r->assertOk();$this->assertSame([],$r->viewData('rows'));
        $this->post(route('admin.market-research.preview'),['sku'=>$p->sku,'sources'=>['https://other.example.test']])->assertSessionHasErrors('sources.0');Http::assertSentCount(1);
    }
    public function test_empty_configuration_makes_no_request(): void
    {
        $this->loginAs();$p=$this->product();config(['hnb_market_research.sources'=>[]]);Http::fake();
        $this->get(route('admin.market-research.index'))->assertOk()->assertSee('Aucune source tarifaire configurée');
        $this->post(route('admin.market-research.preview'),['sku'=>$p->sku,'sources'=>['vendor_a']])->assertSessionHasErrors('sources.0');Http::assertNothingSent();
    }
    public function test_local_report_and_explicit_ai_share_public_facts_only(): void
    {
        $this->loginAs();$p=$this->product();$this->observation($p);
        config(['commercial_ai.enabled'=>true,'commercial_ai.key'=>'test-key']);Http::preventStrayRequests();
        Http::fake(['https://api.deepseek.com/chat/completions'=>Http::response(['choices'=>[['message'=>['content'=>'Vérifier O1.']]]])]);
        $this->post(route('admin.market-research.report'),['sku'=>$p->sku])->assertOk()->assertSee('134,56 MAD');Http::assertNothingSent();
        $this->post(route('admin.market-research.report'),['sku'=>$p->sku,'use_ai'=>'1'])->assertOk()->assertSee('Vérifier O1.');
        Http::assertSent(function($r){$facts=json_decode($r->data()['messages'][1]['content'],true);return count($facts)===1 && $facts[0]['sku']==='QA-F18' && $facts[0]['ref']==='O1' && (float)$facts[0]['public_price_MAD']===1100.0 && !isset($facts[0]['catalog_price']) && !isset($facts[0]['source_url']) && !str_contains(json_encode($facts),'1234.56');});
        Http::assertSentCount(1);$this->assertDatabaseCount('hnb_market_observations',1);
    }
    public function test_ai_failure_preserves_local_report_and_empty_report_never_calls_ai(): void
    {
        $this->loginAs();$p=$this->product();$this->observation($p);config(['commercial_ai.enabled'=>true,'commercial_ai.key'=>'test-key']);
        Http::preventStrayRequests();Http::fake(['https://api.deepseek.com/chat/completions'=>Http::response([],503)]);
        $this->post(route('admin.market-research.report'),['sku'=>'UNKNOWN','use_ai'=>'1'])->assertOk()->assertSee('Aucune observation');Http::assertNothingSent();
        $this->post(route('admin.market-research.report'),['sku'=>$p->sku,'use_ai'=>'1'])->assertOk()->assertSee('Analyse IA indisponible')->assertSee('134,56 MAD');Http::assertSentCount(1);
    }
}
