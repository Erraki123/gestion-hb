<?php
namespace Tests\Unit;
use App\Services\HnbQuoteLearningModel;
use PHPUnit\Framework\TestCase;

class HnbQuoteLearningModelTest extends TestCase
{
    private function rows(bool $signal=true): array
    {
        $rows=[];
        for($i=0;$i<120;$i++) {
            $at=gmdate('Y-m-d H:i:s',1700000000+$i*86400);
            $rows[]=['quote_id'=>$i+1,'client_id'=>$i+1,'captured_at'=>$at,'observed_at'=>gmdate('Y-m-d H:i:s',1700000000+$i*86400+3600),'outcome'=>$i%2,'amount_ttc'=>10000,'age_days'=>$signal?($i%2?1:80):20];
        }
        return $rows;
    }
    public function test_small_data_never_generates_weights(): void
    {
        $r=(new HnbQuoteLearningModel)->evaluate(array_slice($this->rows(),0,50));
        $this->assertFalse($r['ready']); $this->assertNull($r['weights']);
    }
    public function test_learns_signal_and_beats_baseline_on_later_clients(): void
    {
        $model=new HnbQuoteLearningModel; $r=$model->evaluate($this->rows());
        $this->assertTrue($r['ready']); $this->assertSame(96,$r['train']); $this->assertSame(24,$r['test']);
        $this->assertLessThan($r['baseline'],$r['brier']);
        $this->assertGreaterThan($model->predict($r['weights'],10000,80),$model->predict($r['weights'],10000,1));
    }
    public function test_no_signal_never_publishes_scores(): void
    {
        $r=(new HnbQuoteLearningModel)->evaluate($this->rows(false));
        $this->assertFalse($r['ready']); $this->assertNull($r['weights']);
    }
    public function test_client_overlap_and_future_labels_are_excluded(): void
    {
        $rows=$this->rows();
        foreach($rows as $i=>&$r) { if($i<40)$r['client_id']=97; elseif($i<80)$r['observed_at']='2099-01-01 00:00:00'; } unset($r);
        $r=(new HnbQuoteLearningModel)->evaluate($rows);
        $this->assertFalse($r['ready']); $this->assertSame(16,$r['train']);
    }
}
