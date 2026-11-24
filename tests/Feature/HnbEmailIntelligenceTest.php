<?php
namespace Tests\Feature;
use App\Models\{Client,User,HnbEmailMessage as Outbound,ProspectDetail};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Mail,Http};
use Illuminate\Support\Str;
use Tests\TestCase;
class HnbEmailIntelligenceTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void {
        parent::setUp(); $this->withoutVite();
        config()->set(['notify.email.enabled'=>true,'mail.default'=>'smtp','mail.mailers.smtp.transport'=>'smtp','commercial_ai.enabled'=>false]);
        Http::preventStrayRequests();
    }
    private function user(string $role='commercial'): User { return User::factory()->create(['role'=>$role,'is_active'=>true]); }
    private function client(User $owner,string $address='client@example.test'): Client { return Client::create(['commercial_id'=>$owner->id,'name'=>'Client privé','email'=>$address]); }
    private function url(Client $client,string $action='send'): string {return '/email-commercial/client/'.$client->id.($action===''?'':'/'.$action);}
    private function payload(array $extra=[]): array {return array_merge(['request_key'=>(string)Str::uuid(),'subject'=>'Offre','body'=>'Proposition de remise 20%.','confirm'=>'1'],$extra);}
    private function mockSend(string $recipient,string $subject,string $body): void {
        Mail::shouldReceive('raw')->once()->withArgs(function($text,$callback) use($recipient,$subject,$body){
            $message=\Mockery::mock(\Illuminate\Mail\Message::class);
            $message->shouldReceive('to')->once()->with($recipient)->andReturnSelf();
            $message->shouldReceive('subject')->once()->with($subject)->andReturnSelf();
            $callback($message); return $text===$body;
        })->andReturn(new \stdClass());
    }
    public function test_guest_and_other_commercial_cannot_access_or_send_or_approve(): void {
        Mail::shouldReceive('raw')->never();
        $a=$this->user();$b=$this->user();$c=$this->client($b);
        $this->get($this->url($c,''))->assertRedirect(route('login'));
        $this->actingAs($a)->get($this->url($c,''))->assertNotFound();
        $this->post($this->url($c,'draft'),['language'=>'fr'])->assertNotFound();
        $this->post($this->url($c),$this->payload())->assertNotFound();
        $this->get('/email-commercial/pending')->assertForbidden();
        $this->post('/email-commercial/message/1/decision',['decision'=>'approve','confirm'=>1])->assertForbidden();
        $this->assertDatabaseCount('hnb_email_messages',0); Http::assertNothingSent();
    }
    public function test_commercial_free_text_is_approved_once_by_admin(): void {
        $u=$this->user();$admin=$this->user('admin');$c=$this->client($u);$p=$this->payload();
        $this->mockSend($c->email,$p['subject'],$p['body']);
        $this->actingAs($u)->post($this->url($c),$p)->assertRedirect();
        $r=Outbound::firstOrFail();$this->assertSame('pending',$r->status);
        $this->actingAs($admin)->get('/email-commercial/pending')->assertOk()->assertSee($p['body']);
        $url='/email-commercial/message/'.$r->id.'/decision';
        $this->post($url,['decision'=>'approve','confirm'=>1])->assertRedirect();
        $this->post($url,['decision'=>'approve','confirm'=>1])->assertRedirect();
        $this->assertSame('submitted',$r->fresh()->status);$this->assertEquals($admin->id,$r->fresh()->decided_by);
    }
    public function test_preset_uses_server_text_and_recipient_and_cannot_duplicate(): void {
        $u=$this->user();$c=$this->client($u);$p=$this->payload(['preset'=>'ack_fr','recipient'=>'evil@example.test']);
        [$subject,$body]=\App\Services\HnbEmailConversation::TEMPLATES['ack_fr']; $this->mockSend($c->email,$subject,$body);
        $this->actingAs($u)->post($this->url($c),$p)->assertRedirect();$this->post($this->url($c),$p)->assertRedirect();
        $this->assertDatabaseCount('hnb_email_messages',1);$this->assertDatabaseHas('hnb_email_messages',['recipient'=>$c->email,'subject'=>$subject,'body'=>$body,'status'=>'submitted']);
    }
    public function test_admin_direct_and_rejected_request(): void {
        $u=$this->user();$a=$this->user('admin');$c=$this->client($u);$p=$this->payload();
        $this->mockSend($c->email,$p['subject'],$p['body']);
        $this->actingAs($u)->post($this->url($c),$p)->assertRedirect();$r=Outbound::firstOrFail();
        $this->actingAs($a)->post('/email-commercial/message/'.$r->id.'/decision',['decision'=>'reject','confirm'=>1])->assertRedirect();
        $this->assertSame('rejected',$r->fresh()->status);
        $this->post($this->url($c),$this->payload())->assertRedirect();$this->assertDatabaseHas('hnb_email_messages',['created_by'=>$a->id,'decided_by'=>$a->id,'status'=>'submitted']);
    }
    public function test_changed_email_and_opt_out_block_approval(): void {
        Mail::shouldReceive('raw')->never(); $u=$this->user();$a=$this->user('admin');$c=$this->client($u);
        $this->actingAs($u)->post($this->url($c),$this->payload())->assertRedirect();$r=Outbound::firstOrFail();
        $c->update(['email'=>'changed@example.test']);$this->actingAs($a)->post('/email-commercial/message/'.$r->id.'/decision',['decision'=>'approve','confirm'=>1])->assertRedirect();$this->assertSame('blocked',$r->fresh()->status);
        $this->actingAs($u)->post($this->url($c),$this->payload())->assertRedirect();$r=Outbound::latest('id')->firstOrFail();
        ProspectDetail::create(['client_id'=>$c->id,'do_not_contact'=>true]);
        $this->actingAs($a)->post('/email-commercial/message/'.$r->id.'/decision',['decision'=>'approve','confirm'=>1])->assertRedirect();$this->assertSame('blocked',$r->fresh()->status);
    }
    public function test_ai_only_receives_explicit_summary_and_never_sends_email(): void {
        Mail::shouldReceive('raw')->never();$u=$this->user();$c=$this->client($u);
        config()->set(['commercial_ai.enabled'=>true,'commercial_ai.key'=>'fake-secret']);
        Http::fake(['api.deepseek.com/*'=>Http::response(['choices'=>[['message'=>['content'=>'Brouillon test']]]],200)]);
        $this->actingAs($u)->post($this->url($c,'draft'),['language'=>'fr','summary'=>'Besoin de documentation','use_ai'=>1])->assertOk()->assertSee('Brouillon test');
        Http::assertSentCount(1);Http::assertSent(fn($r)=>str_contains($r['messages'][1]['content'],'Besoin de documentation')&&!str_contains($r['messages'][1]['content'],$c->email)&&!str_contains($r['messages'][1]['content'],$c->name));
        $this->assertDatabaseCount('hnb_email_messages',0);
    }
    public function test_disabled_smtp_shared_address_and_missing_confirmation_are_blocked(): void {
        Mail::shouldReceive('raw')->never();$u=$this->user();$c=$this->client($u);$this->actingAs($u);
        $this->postJson($this->url($c),$this->payload(['preset'=>'ack_fr','confirm'=>false]))->assertUnprocessable();
        config()->set('mail.default','log');$this->postJson($this->url($c),$this->payload(['preset'=>'ack_fr']))->assertUnprocessable();
        config()->set('mail.default','smtp');$this->client($this->user(),strtoupper($c->email));$this->postJson($this->url($c),$this->payload(['preset'=>'ack_fr']))->assertUnprocessable();$this->assertDatabaseCount('hnb_email_messages',0);
    }
    public function test_uncertain_smtp_outcome_is_not_automatically_retried(): void {
        $u=$this->user();$c=$this->client($u);$p=$this->payload(['preset'=>'ack_fr']);
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('Network error'));
        $this->actingAs($u)->post($this->url($c),$p)->assertRedirect();$this->post($this->url($c),$p)->assertRedirect();
        $this->assertDatabaseHas('hnb_email_messages',['status'=>'unconfirmed']);$this->assertDatabaseCount('hnb_email_messages',1);
    }
}
