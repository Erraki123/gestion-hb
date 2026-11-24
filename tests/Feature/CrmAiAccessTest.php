<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmAiAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_ask_crm_question(): void
    {
        $this->post('/assistant-commercial/donnees', ['domain'=>'clients','question'=>'Liste des clients'])->assertRedirect('/login');
    }

    public function test_commercial_can_open_read_only_crm_assistant(): void
    {
        $user=User::factory()->create(['role'=>'commercial','is_active'=>true]);
        $this->actingAs($user)->get('/assistant-commercial/donnees')->assertOk();
    }
}
