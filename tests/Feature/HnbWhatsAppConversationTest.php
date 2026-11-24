<?php
namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use App\Models\WhatsAppInboundMessage;
use App\Models\HnbWhatsAppOutboundMessage as Outbound;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class HnbWhatsAppConversationTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp(); $this->withoutVite();
        config()->set(['notify.whatsapp.enabled'=>true,'notify.whatsapp.provider'=>'ultramsg','notify.whatsapp.ultramsg_instance'=>'instance123','notify.whatsapp.ultramsg_token'=>'fake-token','notify.whatsapp.country_code'=>'212']);
        Http::preventStrayRequests();
        Http::fake(['api.ultramsg.com/*'=>Http::response(['sent'=>'true','id'=>'test-provider-id'],200)]);
    }
    private function user(string $role='commercial'): User { return User::factory()->create(['role'=>$role,'is_active'=>true]); }
    private function message(User $u,string $phone='0611111111'): WhatsAppInboundMessage
    {
        $client=Client::create(['commercial_id'=>$u->id,'name'=>'Test client','phone'=>$phone]);
        return WhatsAppInboundMessage::create(['client_id'=>$client->id,'sender'=>\App\Services\WhatsAppService::normalize($phone),'provider_message_id'=>(string)Str::uuid(),'body'=>'Bonjour test','received_at'=>now()]);
    }
    private function payload(array $extra=[]): array { return array_merge(['request_key'=>(string)Str::uuid(),'confirm'=>'1','body'=>'Bonjour, offre spéciale : remise 20%.'],$extra); }
    private function url(WhatsAppInboundMessage $m,string $action='send'): string { return '/whatsapp-assistant/'.$m->id.'/'.$action; }
    public function test_commercial_cannot_send_to_other_client_or_approve(): void
    {
        $a=$this->user(); $b=$this->user(); $m=$this->message($b);
        $this->actingAs($a)->post($this->url($m),$this->payload())->assertNotFound();
        $this->get($this->url($m,'conversation'))->assertNotFound();
        $this->get('/whatsapp-assistant/pending')->assertForbidden();
        $this->post('/whatsapp-assistant/outbound/1/decision',['decision'=>'approve','confirm'=>1])->assertForbidden();
        $this->assertDatabaseCount('hnb_whatsapp_outbound_messages',0); Http::assertNothingSent();
    }
    public function test_commercial_template_ignores_tampered_text_and_duplicate_request(): void
    {
        $u=$this->user(); $m=$this->message($u); $p=$this->payload(['preset'=>'ack_fr','recipient'=>'212699999999']);
        $this->actingAs($u)->post($this->url($m),$p)->assertRedirect($this->url($m,'conversation'));
        $this->post($this->url($m),$p)->assertRedirect();
        Http::assertSentCount(1);
        Http::assertSent(fn($r)=>$r['to']==='+212611111111' && $r['body']===\App\Services\HnbWhatsAppConversation::TEMPLATES['ack_fr']);
        $this->assertDatabaseHas('hnb_whatsapp_outbound_messages',['status'=>'accepted','created_by'=>$u->id,'recipient'=>'212611111111']);
        $this->assertDatabaseCount('hnb_whatsapp_outbound_messages',1);
        $this->get($this->url($m,'conversation'))->assertOk()->assertSee('Accepté par le fournisseur');
    }
    public function test_free_text_waits_for_admin_and_approval_sends_once(): void
    {
        $u=$this->user(); $admin=$this->user('admin'); $m=$this->message($u); $p=$this->payload();
        $this->actingAs($u)->post($this->url($m),$p)->assertRedirect();
        $r=Outbound::firstOrFail(); $this->assertSame('pending',$r->status); Http::assertNothingSent();
        $this->actingAs($admin)->get('/whatsapp-assistant/pending')->assertOk()->assertSee($p['body']);
        $url='/whatsapp-assistant/outbound/'.$r->id.'/decision';
        $this->post($url,['decision'=>'approve','confirm'=>1])->assertRedirect();
        $this->post($url,['decision'=>'approve','confirm'=>1])->assertRedirect();
        $this->assertSame('accepted',$r->fresh()->status); $this->assertEquals($admin->id,$r->fresh()->decided_by); Http::assertSentCount(1);
    }
    public function test_admin_direct_and_rejection_never_require_or_send_extra(): void
    {
        $u=$this->user(); $admin=$this->user('admin'); $m=$this->message($u);
        $this->actingAs($u)->post($this->url($m),$this->payload())->assertRedirect();
        $r=Outbound::firstOrFail(); $this->actingAs($admin)->post('/whatsapp-assistant/outbound/'.$r->id.'/decision',['decision'=>'reject','confirm'=>1])->assertRedirect();
        $this->assertSame('rejected',$r->fresh()->status); Http::assertNothingSent();
        $this->post($this->url($m),$this->payload())->assertRedirect();
        $this->assertDatabaseHas('hnb_whatsapp_outbound_messages',['created_by'=>$admin->id,'decided_by'=>$admin->id,'status'=>'accepted']); Http::assertSentCount(1);
    }
    public function test_changed_client_number_blocks_approved_send(): void
    {
        $u=$this->user(); $admin=$this->user('admin'); $m=$this->message($u);
        $this->actingAs($u)->post($this->url($m),$this->payload())->assertRedirect();
        $r=Outbound::firstOrFail(); $this->actingAs($admin); Client::findOrFail($m->client_id)->update(['phone'=>'0622222222']);
        $this->post('/whatsapp-assistant/outbound/'.$r->id.'/decision',['decision'=>'approve','confirm'=>1])->assertRedirect();
        $this->assertSame('blocked',$r->fresh()->status); Http::assertNothingSent();
    }
    public function test_disabled_gateway_and_confirmation_do_not_send(): void
    {
        $u=$this->user(); $m=$this->message($u);
        $this->actingAs($u)->postJson($this->url($m),$this->payload(['preset'=>'ack_fr','confirm'=>false]))->assertUnprocessable();
        config()->set('notify.whatsapp.enabled',false);
        $this->postJson($this->url($m),$this->payload(['preset'=>'ack_fr']))->assertUnprocessable();
        $this->assertDatabaseCount('hnb_whatsapp_outbound_messages',0); Http::assertNothingSent();
    }
    public function test_unknown_provider_result_does_not_retry(): void
    {
        $u=$this->user(); $m=$this->message($u);
        // Replace actual gateway with mock of an uncertain remote outcome; never call live API.
        $wa=\Mockery::mock(\App\Services\WhatsAppService::class)->makePartial();
        $wa->shouldReceive('sendText')->once()->andThrow(new \App\Services\WhatsAppException('Unknown outcome'));
        $this->app->instance(\App\Services\WhatsAppService::class,$wa);
        $p=$this->payload(['preset'=>'ack_fr']);
        $this->actingAs($u)->post($this->url($m),$p)->assertRedirect();
        $this->post($this->url($m),$p)->assertRedirect();
        $this->assertDatabaseHas('hnb_whatsapp_outbound_messages',['status'=>'unconfirmed']); Http::assertNothingSent();
    }
}
