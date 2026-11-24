<?php
namespace Tests\Feature;

use App\Models\User;
use App\Services\HnbQuoteLearning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HnbQuoteLearningTest extends TestCase
{
    use RefreshDatabase;
    private function loginAs(string $role='admin'): User { $this->withoutVite(); $u=User::factory()->create(['role'=>$role,'is_active'=>true]); $this->actingAs($u); return $u; }
    private function quote(User $u, array $overrides=[]): int
    {
        $c=DB::table('clients')->insertGetId(['name'=>'QA','commercial_id'=>$u->id,'status'=>'prospect','created_at'=>now(),'updated_at'=>now()]);
        return DB::table('quotes')->insertGetId(array_replace(['number'=>'QA-'.uniqid(),'client_id'=>$c,'commercial_id'=>$u->id,'status'=>'brouillon','issue_date'=>today()->subDays(5),'valid_until'=>today()->addDays(10),'total_ttc'=>1200,'approval_status'=>'approved','created_at'=>now(),'updated_at'=>now()],$overrides));
    }
    private function order(int $id,string $status='confirmee',string $approval='approved'): void
    {
        $q=DB::table('quotes')->where('id',$id)->first();
        DB::table('orders')->insert(['number'=>'QA-O-'.uniqid(),'quote_id'=>$id,'client_id'=>$q->client_id,'commercial_id'=>$q->commercial_id,'status'=>$status,'approval_status'=>$approval,'created_at'=>now(),'updated_at'=>now()]);
    }
    public function test_guest_and_commercial_cannot_access_learning(): void
    {
        $this->get(route('admin.quote-learning.index'))->assertRedirect(route('login'));
        $this->loginAs('commercial');
        $this->get(route('admin.quote-learning.index'))->assertForbidden();
        $this->post(route('admin.quote-learning.collect'))->assertForbidden();
        $this->post(route('admin.quote-learning.report'))->assertForbidden();
    }
    public function test_only_eligible_quote_is_captured_once_without_business_writes(): void
    {
        $u=$this->loginAs(); $id=$this->quote($u); $this->quote($u,['status'=>'refuse']); $this->quote($u,['approval_status'=>'pending']); $this->quote($u,['valid_until'=>today()->subDay()]);
        $converted=$this->quote($u); $this->order($converted,'annulee');
        Http::fake();
        $this->post(route('admin.quote-learning.collect'))->assertRedirect(route('admin.quote-learning.index'));
        DB::table('quotes')->where('id',$id)->update(['total_ttc'=>1800]);
        $this->post(route('admin.quote-learning.collect'))->assertRedirect(route('admin.quote-learning.index'));
        $this->assertDatabaseCount('hnb_quote_learning_samples',1);
        $s=DB::table('hnb_quote_learning_samples')->first(); $this->assertSame($id,(int)$s->quote_id); $this->assertSame(1200.0,(float)$s->amount_ttc); $this->assertSame(5,(int)$s->age_days); $this->assertNull($s->outcome);
        $this->assertEquals(1800,DB::table('quotes')->where('id',$id)->value('total_ttc')); Http::assertNothingSent();
    }
    public function test_only_confirmed_approved_orders_and_refusals_are_observed(): void
    {
        $u=$this->loginAs(); $won=$this->quote($u); $lost=$this->quote($u); $pending=$this->quote($u); $cancelled=$this->quote($u); $expired=$this->quote($u);
        $learning=app(HnbQuoteLearning::class); $this->assertSame(5,$learning->collect()['captured']);
        $this->travel(1)->days();
        $this->order($won); $this->order($pending,'nouvelle','pending'); $this->order($cancelled,'annulee');
        DB::table('quotes')->where('id',$lost)->update(['status'=>'refuse']); DB::table('quotes')->where('id',$expired)->update(['status'=>'expire']);
        $this->assertSame(2,$learning->collect()['resolved']); $this->assertSame(0,$learning->collect()['resolved']);
        $this->assertDatabaseHas('hnb_quote_learning_samples',['quote_id'=>$won,'outcome'=>1]); $this->assertDatabaseHas('hnb_quote_learning_samples',['quote_id'=>$lost,'outcome'=>0]);
        foreach([$pending,$cancelled,$expired] as $id) $this->assertDatabaseHas('hnb_quote_learning_samples',['quote_id'=>$id,'outcome'=>null]);
        $this->travelBack();
    }
    public function test_report_with_insufficient_data_returns_page_and_no_prediction(): void
    {
        $u=$this->loginAs(); $this->quote($u); Http::fake();
        $this->get(route('admin.quote-learning.index'))->assertOk()->assertSee('Historique observé');
        $r=$this->post(route('admin.quote-learning.report'))->assertOk()->assertSee('Aucune prédiction publiée');
        $this->assertFalse($r->viewData('report')['evaluation']['ready']); $this->assertSame([],$r->viewData('report')['scores']); $this->assertDatabaseCount('hnb_quote_learning_samples',0); Http::assertNothingSent();
    }
}
