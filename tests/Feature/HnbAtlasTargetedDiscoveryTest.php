<?php
namespace Tests\Feature;

use App\Models\Client;
use App\Models\DiscoveredProspect;
use App\Models\User;
use App\Services\ProspectDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HnbAtlasTargetedDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    private function enable(): void
    {
        config(['prospect_discovery.enabled'=>true,'prospect_discovery.osm_enabled'=>true,
            'prospect_discovery.overpass_url'=>'https://overpass.example.test/api',
            'prospect_discovery.approved_feeds'=>['test'=>'https://feed.example.test/data']]);
        Http::preventStrayRequests();
    }

    public function test_targeted_hotel_discovery_previews_and_saves_once(): void
    {
        $this->enable();
        $item=['type'=>'node','id'=>123,'tags'=>['name'=>'Hotel QA','tourism'=>'hotel','phone'=>'+212612345678']];
        Http::fake(['https://overpass.example.test/api'=>Http::response(['elements'=>[$item,$item]])]);
        $service=app(ProspectDiscoveryService::class);
        $report=$service->discover(true,'Casablanca','hotellerie');
        $this->assertSame(1,$report['new']);
        $this->assertSame(1,$report['existing']);
        $this->assertDatabaseCount('discovered_prospects',0);
        $service->discover(false,'Casablanca','hotellerie');
        $this->assertDatabaseHas('discovered_prospects',['source_id'=>'node:123','city'=>'Casablanca','category'=>'client_final','status'=>'pending']);
        $this->assertSame(0,$service->discover(false,'Casablanca','hotellerie')['new']);
        Http::assertSentCount(3);
        Http::assertSent(fn($request)=>str_contains((string)$request['data'],'33.5731,-7.5898') && str_contains((string)$request['data'],'tourism'));
    }

    public function test_invalid_sector_or_disabled_osm_makes_no_network_calls(): void
    {
        $this->enable(); Http::fake();
        $this->artisan('prospects:discover',['--city'=>'Casablanca','--sector'=>'arbitrary'])->assertExitCode(1);
        config(['prospect_discovery.osm_enabled'=>false]);
        $this->artisan('prospects:discover',['--city'=>'Casablanca','--sector'=>'sante'])->assertExitCode(1);
        Http::assertNothingSent();
    }

    private function candidate(array $extra=[]): DiscoveredProspect
    {
        return DiscoveredProspect::create(array_merge(['source'=>'qa','source_id'=>'1','company'=>'Entreprise QA','city'=>'Casablanca','category'=>'client_final','phone'=>'+212612345678','status'=>'pending'], $extra));
    }

    public function test_normalized_duplicate_contact_cannot_be_imported(): void
    {
        $admin=User::factory()->create(['role'=>'admin','is_active'=>true]);
        $commercial=User::factory()->create(['role'=>'commercial','is_active'=>true]);
        Client::create(['name'=>'Existing','commercial_id'=>$commercial->id,'phone'=>'06 12 34 56 78','status'=>'actif']);
        $candidate=$this->candidate();
        $this->actingAs($admin)->post(route('admin.discovery.accept',$candidate),['commercial_id'=>$commercial->id])->assertSessionHasErrors('candidate');
        $this->assertSame('pending',$candidate->fresh()->status);
        $this->assertDatabaseCount('clients',1);
    }

    public function test_competitor_cannot_be_imported_as_customer(): void
    {
        $admin=User::factory()->create(['role'=>'admin','is_active'=>true]);
        $commercial=User::factory()->create(['role'=>'commercial','is_active'=>true]);
        $candidate=$this->candidate(['category'=>'concurrent']);
        $this->actingAs($admin)->post(route('admin.discovery.accept',$candidate),['commercial_id'=>$commercial->id])->assertSessionHasErrors('candidate');
        $this->assertSame('pending',$candidate->fresh()->status);
        $this->assertDatabaseCount('clients',0);
    }

    public function test_only_admin_can_accept_valid_prospect(): void
    {
        $admin=User::factory()->create(['role'=>'admin','is_active'=>true]);
        $commercial=User::factory()->create(['role'=>'commercial','is_active'=>true]);
        $candidate=$this->candidate();
        $route=route('admin.discovery.accept',$candidate);
        $this->actingAs($commercial)->post($route,['commercial_id'=>$commercial->id])->assertForbidden();
        $this->actingAs($admin)->post($route,['commercial_id'=>$commercial->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('accepted',$candidate->fresh()->status);
        $this->assertDatabaseHas('clients',['commercial_id'=>$commercial->id,'status'=>'prospect','phone'=>'+212612345678']);
    }
}
