<?php
namespace Tests\Unit;

use App\Services\AtlasOpportunityService;
use PHPUnit\Framework\TestCase;

class AtlasOpportunityServiceTest extends TestCase
{
    public function test_matches_real_catalog_terms_without_inventing_needs(): void
    {
        $s=new AtlasOpportunityService();
        $products=[
            (object)['id'=>1,'name'=>'ZKTeco contrôle accès biométrique','is_active'=>true,'stock_quantity'=>3],
            (object)['id'=>2,'name'=>'Caméra CCTV','is_active'=>true,'stock_quantity'=>0],
            (object)['id'=>3,'name'=>'Smart Home G4','is_active'=>false,'stock_quantity'=>3],
        ];
        $result=$s->recommend(['company'=>'Sécurité Accès Casablanca','category'=>'installateur','phone'=>null,'email'=>null],$products);
        $this->assertSame('Partenariat technique potentiel',$result['purpose']);
        $this->assertCount(1,$result['products']);
        $this->assertSame('ZKTeco contrôle accès biométrique',$result['products'][0]['name']);
        $this->assertFalse($result['contact_ready']);
    }
    public function test_competitors_are_not_treated_as_sales_leads(): void
    {
        $r=(new AtlasOpportunityService())->recommend(['company'=>'Concurrence','category'=>'concurrent'],[(object)['id'=>1,'name'=>'Caméra CCTV','is_active'=>true,'stock_quantity'=>3]]);
        $this->assertSame([],$r['products']);
        $this->assertFalse($r['contact_ready']);
        $this->assertStringContainsString('Comparer',$r['next_action']);
    }
}
