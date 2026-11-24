<?php
namespace Tests\Unit;

use App\Services\AtlasContactDecisionService;
use PHPUnit\Framework\TestCase;

class AtlasContactDecisionServiceTest extends TestCase
{
    public function test_fingerprint_changes_when_provenance_or_contact_changes(): void
    {
        $service = new AtlasContactDecisionService();
        $a = ['phone' => '+212 537 360331', 'email' => '', 'website'=>'', 'evidence_url' => 'https://www.openstreetmap.org/node/1'];
        $this->assertSame($service->fingerprint($a), $service->fingerprint($a));
        $b = $a; $b['phone'] = '+212 537 360332';
        $this->assertNotSame($service->fingerprint($a), $service->fingerprint($b));
        $c = $a; $c['evidence_url'] = 'https://www.openstreetmap.org/node/2';
        $this->assertNotSame($service->fingerprint($a), $service->fingerprint($c));
    }
}
