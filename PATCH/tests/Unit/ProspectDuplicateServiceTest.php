<?php

namespace Tests\Unit;

use App\Services\ProspectDuplicateService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProspectDuplicateServiceTest extends TestCase
{
    #[Test]
    public function it_matches_moroccan_phone_variants(): void
    {
        $service = new ProspectDuplicateService();
        $this->assertTrue($service->samePhone('06 12 34 56 78', '+212 612-345-678'));
        $this->assertTrue($service->samePhone('00212 612345678', '0612345678'));
        $this->assertTrue($service->samePhone('612345678', '0612345678'));
        $this->assertFalse($service->samePhone('0612345678', '0612345679'));
    }

    #[Test]
    public function it_handles_emails_and_rejects_empty_contacts(): void
    {
        $service = new ProspectDuplicateService();
        $this->assertTrue($service->sameEmail(' Contact@Example.com ', 'contact@example.com'));
        $this->assertFalse($service->sameEmail(null, 'contact@example.com'));
        $this->assertFalse($service->samePhone('', ''));
        $this->assertNull($service->normalizePhone('abc'));
    }
}
