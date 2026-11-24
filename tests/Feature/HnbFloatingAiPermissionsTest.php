<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HnbFloatingAiPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/admin/ai-floating/ask';

    public function test_guest_cannot_use_floating_assistant(): void
    {
        $this->postJson(self::ENDPOINT, ['question' => 'Liste des clients'])
            ->assertUnauthorized();
    }

    public function test_admin_can_query_floating_assistant(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)
            ->postJson(self::ENDPOINT, ['question' => 'Liste des clients'])
            ->assertOk()
            ->assertJsonStructure(['answer', 'error']);
    }

    public function test_commercial_sees_only_own_clients_and_admin_sees_both(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $first = User::factory()->create(['role' => 'commercial', 'is_active' => true]);
        $second = User::factory()->create(['role' => 'commercial', 'is_active' => true]);

        Client::create(['commercial_id' => $first->id, 'name' => 'Client Alpha Unique']);
        Client::create(['commercial_id' => $second->id, 'name' => 'Client Beta Secret']);

        $commercialResponse = $this->actingAs($first)->postJson(self::ENDPOINT, [
            'question' => 'Liste des clients',
        ])->assertOk()->json('answer');
        $this->assertIsString($commercialResponse);
        $this->assertStringContainsString('Client Alpha Unique', $commercialResponse);
        $this->assertStringNotContainsString('Client Beta Secret', $commercialResponse);

        $adminResponse = $this->actingAs($admin)->postJson(self::ENDPOINT, [
            'question' => 'Liste des clients',
        ])->assertOk()->json('answer');
        $this->assertStringContainsString('Client Alpha Unique', $adminResponse);
        $this->assertStringContainsString('Client Beta Secret', $adminResponse);
    }

    public function test_sensitive_command_is_not_executed_by_floating_assistant(): void
    {
        $commercial = User::factory()->create(['role' => 'commercial', 'is_active' => true]);
        $response = $this->actingAs($commercial)->postJson(self::ENDPOINT, [
            'question' => 'Change le prix de tous les produits',
        ])->assertOk();

        $this->assertIsString($response->json('answer'));
        $this->assertStringContainsString('pas exécutée', $response->json('answer'));
    }
}
