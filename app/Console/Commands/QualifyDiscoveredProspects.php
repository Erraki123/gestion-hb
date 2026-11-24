<?php
namespace App\Console\Commands;

use App\Models\DiscoveredProspect;
use App\Services\ProspectQualificationService;
use Illuminate\Console\Command;

class QualifyDiscoveredProspects extends Command
{
    protected $signature='prospects:qualify {--dry-run : Preview scores, do not save or call AI} {--limit= : Number of pending unscored companies, max 30} {--rescore : Requalify pending companies already scored}';
    protected $description='Rank discovered business suggestions; optional DeepSeek, no outreach or CRM imports';
    public function handle(ProspectQualificationService $service): int
    {
        $limit=max(1,min(30,(int)($this->option('limit') ?: config('prospect_qualification.daily_limit',8))));
        $query=DiscoveredProspect::query()->where('status','pending');
        if (!$this->option('rescore')) $query->whereNull('qualified_at');
        $candidates=$query->orderBy('id')->limit($limit)->get();
        $this->line('Candidates: '.$candidates->count().' | mode: '.($this->option('dry-run')?'DRY RUN':'SAVE'));
        foreach ($candidates as $item) {
            $result=$service->qualify($item,!$this->option('dry-run'));
            $this->line('#'.$item->id.' | '.$item->company.' | '.$result['score'].'/100 | '.$result['method'].' | '.$result['reason']);
            if (!$this->option('dry-run')) $item->forceFill([
                'qualification_score'=>$result['score'],
                'qualification_reason'=>$result['reason'],
                'qualification_method'=>$result['method'],
                'qualified_at'=>now(),
            ])->save();
        }
        return self::SUCCESS;
    }
}
