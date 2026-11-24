<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HnbUnifiedNewPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approvals_uses_client_style_table(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->get(route('admin.approvals.index'))
            ->assertOk()->assertSee('Validations')->assertSee('hnb-ap-table');
    }

    public function test_direction_notifications_use_compact_table(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->get(route('admin.direction.notifications.index'))
            ->assertOk()->assertSee('Centre de notifications')->assertSee('hnb-v2-table');
    }

    public function test_commercial_actions_use_compact_table(): void
    {
        $commercial = User::factory()->create(['role' => 'commercial', 'is_active' => true]);
        $this->actingAs($commercial)->get(route('hnb.routines.index'))
            ->assertOk()->assertSee('Actions à traiter')->assertSee('hnb-v2-table');
    }

    public function test_unified_stylesheet_is_scoped_to_new_pages(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $css = file_get_contents(public_path('css/hnb-unified-pages-v1.css'));
        $this->assertStringContainsString("css/hnb-unified-pages-v1.css", $layout);
        $this->assertStringContainsString('.hnb-v2-page', $css);
        $this->assertStringContainsString('.hnb-unified-page', $css);
        $this->assertStringNotContainsString('.clients-compact', $css);
    }
}
