<?php
namespace Tests\Unit;
use App\Services\ProspectDiscoveryService;
use PHPUnit\Framework\TestCase;
class ProspectDiscoveryServiceTest extends TestCase {
    public function test_osm_business_normalization(): void {
        $s=new ProspectDiscoveryService;
        $r=$s->normalizeOsm(['type'=>'node','id'=>321,'tags'=>['name'=>'Casa Sécurité','phone'=>'+212500000000','website'=>'https://example.org']], 'Casablanca');
        $this->assertSame('node:321',$r['source_id']);
        $this->assertSame('Casablanca',$r['city']);
        $this->assertSame('openstreetmap',$r['source']);
    }
    public function test_missing_business_name_ignored(): void {
        $this->assertNull((new ProspectDiscoveryService)->normalizeOsm(['type'=>'node','id'=>123,'tags'=>[]],'Rabat'));
    }
    public function test_feed_does_not_trust_bad_email(): void {
        $r=(new ProspectDiscoveryService)->normalizeFeed(['id'=>'a1','company'=>'Atlas Integrateur','email'=>'not_an_email'],'licensedfeed');
        $this->assertNull($r['email']);
    }
}
