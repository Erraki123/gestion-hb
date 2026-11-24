<?php
namespace Tests\Unit;

use App\Services\HnbAtlasSearchScore;
use PHPUnit\Framework\TestCase;

class HnbAtlasSearchScoreTest extends TestCase
{
    public function test_separates_catalog_fit_and_completeness(): void
    {
        $s=new HnbAtlasSearchScore();$row=['phone'=>'+212612345678','email'=>'hotel@example.test','city'=>'Casablanca'];
        $r=$s->score($row,'hotellerie',['acces','domotique']);
        $this->assertSame(67,$r['fit']);$this->assertSame(80,$r['completeness']);$this->assertSame(71,$r['priority']);
        $empty=$s->score(['city'=>'Casablanca'],'hotellerie',[]);$this->assertSame(0,$empty['fit']);$this->assertSame(10,$empty['completeness']);
    }
    public function test_invalid_contact_and_competitor_are_not_promoted(): void
    {
        $s=new HnbAtlasSearchScore();$row=['phone'=>'bad','email'=>'bad','website'=>'ftp://example.test','category'=>'concurrent'];
        $r=$s->score($row,'securite',['acces']);$this->assertSame(0,$r['priority']);$this->assertSame(0,$r['completeness']);$this->assertSame([],$r['families']);
    }
}
