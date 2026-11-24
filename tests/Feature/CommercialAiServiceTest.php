<?php

namespace Tests\Feature;

use App\Services\CommercialAiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CommercialAiServiceTest extends TestCase
{
    public function test_disabled_service_does_not_call_network(): void
    {
        config()->set('commercial_ai.enabled', false);
        config()->set('commercial_ai.key', null);
        Http::preventStrayRequests();
        $this->assertFalse(app(CommercialAiService::class)->enabled());
        $this->expectException(\RuntimeException::class);
        app(CommercialAiService::class)->draft('email', 'fr', 'Présenter une solution.');
    }

    public function test_draft_uses_fake_deepseek_and_does_not_send_messages(): void
    {
        config()->set('commercial_ai.enabled', true);
        config()->set('commercial_ai.key', 'test-only-token');
        config()->set('commercial_ai.model', 'deepseek-flash');
        Http::preventStrayRequests();
        Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => 'Bonjour, voici notre offre.']]]], 200)]);
        $result = app(CommercialAiService::class)->draft('email', 'fr', 'Présentation de contrôle d’accès.');
        $this->assertSame('Bonjour, voici notre offre.', $result);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.deepseek.com/chat/completions' && $request['model'] === 'deepseek-flash');
    }
}
