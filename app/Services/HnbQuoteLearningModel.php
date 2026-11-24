<?php

namespace App\Services;

/** Small regularized logistic regression; no external API and no persisted model. */
final class HnbQuoteLearningModel
{
    public function features(float $amount, int $age): array
    {
        return [1.0, log1p(max(0.0, min(9999999999.99, $amount))) / 10.0, max(0, min(90, $age)) / 30.0];
    }

    public function predict(array $weights, float $amount, int $age): float
    {
        $x = $this->features($amount, $age);
        $z = max(-35.0, min(35.0, $weights[0]*$x[0]+$weights[1]*$x[1]+$weights[2]*$x[2]));
        return 1.0 / (1.0 + exp(-$z));
    }

    private function fit(array $rows): array
    {
        $w = [0.0, 0.0, 0.0];
        for ($step = 0; $step < 600; $step++) {
            $g = [0.0, 0.0, 0.0];
            foreach ($rows as $r) {
                $x = $this->features((float)$r['amount_ttc'], (int)$r['age_days']);
                $e = $this->predict($w, (float)$r['amount_ttc'], (int)$r['age_days']) - (int)$r['outcome'];
                for ($j = 0; $j < 3; $j++) $g[$j] += $e*$x[$j];
            }
            for ($j = 0; $j < 3; $j++) $w[$j] -= 0.25*($g[$j]/count($rows)+($j ? 0.01*$w[$j] : 0.0));
        }
        return $w;
    }

    /** Holdout is later in time; no client overlap; training labels must already be known at cutoff. */
    public function evaluate(array $samples): array
    {
        $rows = array_values(array_filter($samples, static fn($r) => in_array($r['outcome'] ?? null, [0,1], true)
            && !empty($r['captured_at']) && !empty($r['observed_at']) && $r['observed_at'] > $r['captured_at']));
        usort($rows, static fn($a,$b) => [$a['captured_at'],$a['quote_id']] <=> [$b['captured_at'],$b['quote_id']]);
        $result = ['ready'=>false,'reason'=>'Données prospectives insuffisantes.','total'=>count($rows),'train'=>0,'test'=>0,'brier'=>null,'baseline'=>null,'weights'=>null];
        if (count($rows) < 80) return $result;
        $cut = $rows[(int)floor(count($rows)*0.8)]['captured_at'];
        $test = array_values(array_filter($rows, static fn($r) => $r['captured_at'] >= $cut));
        $excluded = array_fill_keys(array_column($test,'client_id'), true);
        $train = array_values(array_filter($rows, static fn($r) => $r['captured_at'] < $cut && $r['observed_at'] < $cut && !isset($excluded[$r['client_id']])));
        $result['train']=count($train); $result['test']=count($test);
        $positives = static fn($r) => array_sum(array_column($r,'outcome'));
        $y = $positives($train); $yt = $positives($test);
        if (count($train)<60 || count($test)<20 || min($y,count($train)-$y)<10 || min($yt,count($test)-$yt)<5) {
            $result['reason']='Séparation temporelle / clients distincts ou classes insuffisantes.';
            return $result;
        }
        $w = $this->fit($train);
        $base = $y/count($train); $s=0.0; $b=0.0;
        foreach ($test as $r) {
            $p=$this->predict($w,(float)$r['amount_ttc'],(int)$r['age_days']);
            $s+=($p-$r['outcome'])**2; $b+=($base-$r['outcome'])**2;
        }
        $result['brier']=$s/count($test); $result['baseline']=$b/count($test);
        if ($result['brier'] > $result['baseline']-0.01) {
            $result['reason']='Le modèle ne surpasse pas suffisamment la référence naïve.';
            return $result;
        }
        $result['ready']=true; $result['reason']='Signal expérimental validé sur cet échantillon uniquement.'; $result['weights']=$w;
        return $result;
    }
}
