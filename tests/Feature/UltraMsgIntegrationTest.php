<?php
namespace Tests\Feature;

use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UltraMsgIntegrationTest extends TestCase
{
    public function test_ultramsg_disabled_without_credentials(): void
    {
        config()->set('notify.whatsapp.enabled', true);
        config()->set('notify.whatsapp.provider', 'ultramsg');
        config()->set('notify.whatsapp.ultramsg_instance', null);
        config()->set('notify.whatsapp.ultramsg_token', null);
        $this->assertFalse(app(WhatsAppService::class)->enabled());
    }

    public function test_send_message_uses_ultramsg_form_without_real_network_request(): void
    {
        config()->set('notify.whatsapp.enabled', true);
        config()->set('notify.whatsapp.provider', 'ultramsg');
        config()->set('notify.whatsapp.ultramsg_instance', 'instance123');
        config()->set('notify.whatsapp.ultramsg_token', 'fake-token');
        Http::preventStrayRequests();
        Http::fake(['api.ultramsg.com/*' => Http::response(['sent' => 'true', 'id' => 'msg-test'], 200)]);
        $result = app(WhatsAppService::class)->sendText('0612345678', 'Test HN BLOOM', 'test-001');
        $this->assertSame('msg-test', $result['message_id']);
        Http::assertSent(fn ($r) => $r->url() === 'https://api.ultramsg.com/instance123/messages/chat'
            && $r['to'] === '+212612345678' && $r['body'] === 'Test HN BLOOM'
            && $r['token'] === 'fake-token');
    }
}
