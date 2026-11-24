<?php
namespace Tests\Unit;

use App\Services\AtlasOpportunityService;
use PHPUnit\Framework\TestCase;

class AtlasSectorRecognitionTest extends TestCase
{
    public function test_clinics_are_health_hypotheses_with_relevant_real_stock(): void
    {
        $s = new AtlasOpportunityService();
        $p = [
            (object)['id'=>1,'name'=>'ZKTeco contrôle accès biométrique','is_active'=>true,'stock_quantity'=>2],
            (object)['id'=>2,'name'=>'Caméra CCTV','is_active'=>true,'stock_quantity'=>0],
            (object)['id'=>3,'name'=>'Smart Home G4','is_active'=>true,'stock_quantity'=>3],
        ];
        $r = $s->recommend(['company'=>'Clinique Maâmoura', 'category'=>'client_final'], $p);
        $this->assertSame('sante', $r['sector']);
        $this->assertSame('Faible — activité à vérifier', $r['sector_confidence']);
        $this->assertSame('Très faible — aucun contact direct', $r['data_quality']);
        $this->assertCount(1, $r['products']);
        $this->assertSame('acces', $r['products'][0]['family']);
    }

    public function test_hotel_and_engineering_lab_not_misclassified_as_health(): void
    {
        $s = new AtlasOpportunityService();
        $this->assertSame('hotellerie', $s->detectSector('Hôtel kennedy Hospitality Resort')['sector']);
        $this->assertSame('industrie', $s->detectSector('Le Laboratoire Conception et Systèmes (LCS)')['sector']);
        $this->assertSame('non_determine', $s->detectSector('Entreprise ABC')['sector']);
    }

    public function test_competitor_has_no_product_suggestions(): void
    {
        $s = new AtlasOpportunityService();
        $r = $s->recommend(['company'=>'Sécurité Casablanca', 'category'=>'concurrent'], [(object)['name'=>'Caméra CCTV','stock_quantity'=>5,'is_active'=>true]]);
        $this->assertSame([], $r['products']);
        $this->assertSame([], $r['suggested_families']);
        $this->assertFalse($r['contact_ready']);
    }
    public function test_existing_partner_matching_is_preserved(): void
    {
        $s = new AtlasOpportunityService();
        $products = [
            (object)['id'=>1,'name'=>'ZKTeco contrôle accès biométrique','is_active'=>true,'stock_quantity'=>3],
            (object)['id'=>2,'name'=>'Caméra CCTV','is_active'=>true,'stock_quantity'=>0],
        ];
        $r = $s->recommend(['company'=>'Sécurité Accès Casablanca', 'category'=>'installateur'], $products);
        $this->assertSame('securite_partenaire', $r['sector']);
        $this->assertCount(1, $r['products']);
        $this->assertSame('acces', $r['products'][0]['family']);
    }

    public function test_generic_sector_keeps_available_catalog_as_unverified_suggestion(): void
    {
        $s = new AtlasOpportunityService();
        $r = $s->recommend(['company'=>'Entreprise ABC', 'category'=>'client_final'], [
            (object)['id'=>1,'name'=>'ZKTeco controle acces','is_active'=>true,'stock_quantity'=>1],
        ]);
        $this->assertSame('non_determine', $r['sector']);
        $this->assertCount(1, $r['products']);
        $this->assertFalse($r['products'][0]['relevant']);
    }
}
