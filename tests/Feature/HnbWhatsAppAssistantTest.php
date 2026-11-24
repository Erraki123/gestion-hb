<?php
namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use App\Models\WhatsAppInboundMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HnbWhatsAppAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config()->set('commercial_ai.enabled', false);
        config()->set('notify.whatsapp.country_code', '212');
        Http::preventStrayRequests();
    }
    private function user(string $role = 'commercial'): User
    {
        return User::factory()->create(['role'=>$role,'is_active'=>true]);
    }
    private function message(User $user, string $phone, string $body): WhatsAppInboundMessage
    {
        $client = Client::create(['commercial_id'=>$user->id,'name'=>'Client '.$body,'phone'=>$phone]);
        return WhatsAppInboundMessage::create(['client_id'=>$client->id,'sender'=>\App\Services\WhatsAppService::normalize($phone),'provider_message_id'=>'wa-'.$client->id,'body'=>$body,'received_at'=>now()]);
    }
    public function test_guest_cannot_request_draft(): void
    {
        $this->postJson('/whatsapp-assistant/1/draft', ['language'=>'fr'])->assertUnauthorized();
        Http::assertNothingSent();
    }
    public function test_ownership_is_enforced_for_page_and_draft(): void
    {
        $a=$this->user(); $b=$this->user(); $admin=$this->user('admin');
        $one=$this->message($a,'0611111111','Alpha unique');
        $two=$this->message($b,'0622222222','Beta confidential');
        $this->actingAs($a)->get('/whatsapp-assistant')->assertOk()->assertSee('Alpha unique')->assertDontSee('Beta confidential');
        $this->get('/whatsapp-assistant/'.$two->id.'/compose')->assertNotFound();
        $this->post('/whatsapp-assistant/'.$two->id.'/draft',['language'=>'fr','use_ai'=>1,'summary'=>'Test'])->assertNotFound();
        $this->actingAs($admin)->get('/whatsapp-assistant/'.$two->id.'/compose')->assertOk()->assertSee('Beta confidential');
        Http::assertNothingSent();
    }
    public function test_ambiguous_phone_and_mismatched_sender_are_excluded(): void
    {
        $a=$this->user(); $b=$this->user();
        $one=$this->message($a,'0611111111','Ambiguous secret');
        Client::create(['commercial_id'=>$b->id,'name'=>'Shared phone','phone'=>'+212611111111']);
        $two=$this->message($a,'0622222222','Wrong sender');
        $two->update(['sender'=>'212699999999']);
        $this->actingAs($a)->get('/whatsapp-assistant')->assertOk()->assertDontSee('Ambiguous secret')->assertDontSee('Wrong sender');
        foreach ([$one,$two] as $message) $this->get('/whatsapp-assistant/'.$message->id.'/compose')->assertNotFound();
    }
    public function test_local_draft_and_product_fact_do_not_send_or_modify(): void
    {
        $a=$this->user(); $one=$this->message($a,'0611111111','Question catalogue');
        $p=Product::create(['name'=>'Contrôle accès test','sku'=>'WA-F18','sale_price'=>1850,'stock_quantity'=>7,'is_active'=>true]);
        $r=$this->actingAs($a)->post('/whatsapp-assistant/'.$one->id.'/draft',['language'=>'fr','sku'=>'WA-F18']);
        $r->assertOk()->assertSee('Brouillon modifiable')->assertSee('1850')->assertSee('Contrôle accès test')->assertSee('Aucun message');
        $this->assertNull($one->fresh()->read_at);
        $this->assertEquals(7,(float)$p->fresh()->stock_quantity);
        Http::assertNothingSent();
    }
    public function test_only_explicit_summary_is_sent_to_deepseek(): void
    {
        $a=$this->user(); $one=$this->message($a,'0611111111','PRIVATE-CLIENT-BODY');
        config()->set('commercial_ai.enabled', true);
        config()->set('commercial_ai.key','fake-key');
        Http::fake(['https://api.deepseek.com/chat/completions'=>Http::response(['choices'=>[['message'=>['content'=>'Bonjour, merci pour votre demande.']]]],200)]);
        $url='/whatsapp-assistant/'.$one->id.'/draft';
        $this->actingAs($a)->post($url,['language'=>'fr','summary'=>'Information produit'])->assertOk();
        Http::assertNothingSent();
        $this->post($url,['language'=>'fr','use_ai'=>1,'summary'=>'Information produit'])->assertOk()->assertSee('Bonjour, merci pour votre demande.');
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request->body(),'Information produit') && !str_contains($request->body(),'PRIVATE-CLIENT-BODY') && !str_contains($request->body(),'212611111111'));
        $this->assertNull($one->fresh()->read_at);
    }
    public function test_webhook_leaves_duplicate_phone_unassigned(): void
    {
        $a=$this->user(); $b=$this->user();
        foreach ([$a,$b] as $u) Client::create(['commercial_id'=>$u->id,'name'=>'Duplicate','phone'=>'0611111111']);
        config()->set('ultramsg_inbox.webhook_secret', str_repeat('x',48));
        config()->set('ultramsg_inbox.instance_id','instance12345');
        $this->postJson('/integrations/ultramsg/inbound/'.str_repeat('x',48),['event_type'=>'message_received','instanceId'=>'12345','data'=>['id'=>'duplicate-test-1','from'=>'212611111111@c.us','fromMe'=>false,'type'=>'chat','body'=>'Test']])->assertOk();
        $this->assertDatabaseHas('whatsapp_inbound_messages',['provider_message_id'=>'duplicate-test-1','client_id'=>null]);
    }
}
