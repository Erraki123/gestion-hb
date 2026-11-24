<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DirectionAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_never_sends_an_email(): void
    {
        Mail::fake();
        $this->artisan('direction:alerts', ['--dry-run' => true])->assertSuccessful();
        Mail::assertNothingSent();
    }

    public function test_disabled_notifications_never_send(): void
    {
        config(['direction_alerts.enabled' => false]);
        Mail::fake();
        $this->artisan('direction:alerts')->assertSuccessful();
        Mail::assertNothingSent();
    }
}
