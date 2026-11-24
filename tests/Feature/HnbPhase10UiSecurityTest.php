<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HnbPhase10UiSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_mobile_layout_has_focus_safe_sidebar_and_responsive_search(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->get(route('hnb.routines.index'))->assertOk()
            ->assertSee('id="hnb-main-sidebar"', false)
            ->assertSee(':inert="!sidebar"', false)
            ->assertSee('aria-controls="hnb-main-sidebar"', false)
            ->assertSee('aria-label="Recherche globale"', false)
            ->assertSee('id="hnb-chat-dialog"', false)
            ->assertSee('aria-controls="hnb-chat-dialog"', false);
    }

    public function test_private_pages_disable_cache_and_send_security_headers(): void
    {
        $user = User::factory()->create(['role' => 'commercial', 'is_active' => true]);
        $response = $this->actingAs($user)->get(route('hnb.routines.index'))->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_commercial_mobile_layout_does_not_expose_admin_notifications(): void
    {
        $commercial = User::factory()->create(['role' => 'commercial', 'is_active' => true]);
        $this->actingAs($commercial)->get(route('hnb.routines.index'))->assertOk()
            ->assertSee('aria-label="Recherche globale"', false)
            ->assertDontSee('href="'.route('admin.direction.notifications.index').'"', false);
        $this->get(route('admin.direction.notifications.index'))->assertForbidden();
    }

    public function test_visitor_cannot_open_private_routines(): void
    {
        $this->get(route('hnb.routines.index'))->assertRedirect();
    }
}
