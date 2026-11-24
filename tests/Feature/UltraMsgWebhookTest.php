<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UltraMsgWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('ultramsg_inbox.webhook_secret', str_repeat('x', 48));
        config()->set('ultramsg_inbox.instance_id', 'instance12345');
    }

    public function test_webhook_rejects_wrong_secret(): void
    {
        $this->postJson('/integrations/ultramsg/inbound/'.str_repeat('y', 48), [])->assertForbidden();
    }

    public function test_received_webhook_is_stored_only_once(): void
    {
        $payload = [
            'event_type' => 'message_received', 'instanceId' => '12345',
            'data' => ['id' => 'false_212612345678@c.us_TEST123', 'from' => '212612345678@c.us',
                'fromMe' => false, 'type' => 'chat', 'body' => 'Bonjour', 'time' => 1700000000],
        ];
        $url = '/integrations/ultramsg/inbound/'.str_repeat('x', 48);
        $this->postJson($url, $payload)->assertOk();
        $this->postJson($url, $payload)->assertOk();
        $this->assertSame(1, DB::table('whatsapp_inbound_messages')->count());
    }

    public function test_commercial_cannot_open_admin_inbox(): void
    {
        $user = User::factory()->create(['role' => 'commercial', 'is_active' => true]);
        $this->actingAs($user)->get('/admin/whatsapp-inbox')->assertForbidden();
    }
}
