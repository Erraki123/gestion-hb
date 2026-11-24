<?php

namespace Tests\Unit;

use App\Services\ProspectCsvImportService;
use PHPUnit\Framework\TestCase;

class ProspectCsvImportServiceTest extends TestCase
{
    public function test_normalizes_contact_and_category(): void
    {
        $s=new ProspectCsvImportService();
        $row=$s->normalize(['company'=>'  Atlas Sécurité  ','email'=>'CONTACT@EXAMPLE.ORG','category'=>'installateur','website'=>'https://example.org'],'salon2026');
        $this->assertSame('Atlas Sécurité',$row['company']);
        $this->assertSame('contact@example.org',$row['email']);
        $this->assertSame('installateur',$row['category']);
    }
    public function test_rejects_invalid_names_and_unsafe_urls(): void
    {
        $s=new ProspectCsvImportService();
        $this->assertNull($s->normalize(['company'=>'A'],'annuaire'));
        $r=$s->normalize(['company'=>'Entreprise Test','website'=>'http://127.0.0.1/admin','source_url'=>'file:///tmp/secret'],'annuaire');
        $this->assertNull($r['website']);
        $this->assertNull($r['source_url']);
    }
    public function test_reads_semicolon_csv_without_database(): void
    {
        $path=tempnam(sys_get_temp_dir(),'hncsv');
        try {
            file_put_contents($path,"company;city;category;email\nEntreprise Sécurité;Casa;revendeur;hello@example.org\n");
            $result=(new ProspectCsvImportService())->readCsv($path,'foire');
            $this->assertCount(1,$result['rows']);
            $this->assertSame('revendeur',$result['rows'][0]['category']);
        } finally { @unlink($path); }
    }
}
