<?php
namespace Tests\Unit;

use App\Services\ProspectQualificationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProspectQualificationServiceTest extends TestCase
{
    #[Test] public function strong_security_installer_is_ranked_above_unknown_business(): void
    {
        $svc=new ProspectQualificationService;
        $a=$svc->rules(['company'=>'Installation Vidéosurveillance Casa','category'=>'installateur','city'=>'Casablanca','email'=>'contact@example.org','phone'=>'0600000000','website'=>'https://example.org']);
        $b=$svc->rules(['company'=>'Autre société','category'=>'client_final','city'=>'Casablanca']);
        $this->assertGreaterThan($b['score'],$a['score']);
        $this->assertLessThanOrEqual(100,$a['score']);
    }
    #[Test] public function invalid_deepseek_json_falls_back_to_local_rules(): void
    {
        $svc=new ProspectQualificationService;
        $base=$svc->rules(['company'=>'Unknown','category'=>'client_final']);
        $this->assertSame($base,$svc->parseAiResponse('{"score":999,"reason":"ok"}',$base));
        $this->assertSame($base,$svc->parseAiResponse('not json',$base));
    }
    #[Test] public function valid_deepseek_json_is_accepted_as_a_suggestion(): void
    {
        $svc=new ProspectQualificationService;
        $result=$svc->parseAiResponse('{"score":72,"reason":"Activité potentiellement pertinente"}',['score'=>10,'reason'=>'x','method'=>'rules']);
        $this->assertSame('deepseek',$result['method']);
        $this->assertSame(72,$result['score']);
    }
}
