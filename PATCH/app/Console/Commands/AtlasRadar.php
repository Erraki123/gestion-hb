<?php
namespace App\Console\Commands;

use App\Models\DiscoveredProspect;
use App\Models\Product;
use App\Services\AtlasOpportunityService;
use Illuminate\Console\Command;

class AtlasRadar extends Command
{
    protected $signature='atlas:radar {--discover : Run configured approved discovery sources first} {--limit=20 : Max candidates analyzed (1-100)} {--dry-run : Do not persist discovery or qualification}';
    protected $description='ATLAS Radar: source-backed opportunities and catalog matching; never sends messages';

    public function handle(AtlasOpportunityService $atlas): int
    {
        $limit=max(1,min(100,(int)$this->option('limit')));
        if ($this->option('discover')) {
            $this->line('Discovery from configured sources:');
            $this->call('prospects:discover',$this->option('dry-run')?['--dry-run'=>true]:[]);
        }
        if (!$this->option('dry-run')) {
            $this->call('prospects:qualify',['--limit'=>min($limit,30)]);
        }
        $products=Product::query()->where('is_active',true)->where('stock_quantity','>',0)->get(['id','name','stock_quantity','is_active']);
        $leads=DiscoveredProspect::query()->where('status','pending')
            ->orderByRaw('COALESCE(qualification_score, score) DESC')
            ->limit($limit)->get();
        $this->line('ATLAS | candidates: '.$leads->count().' | stocked product references: '.$products->count());
        foreach($leads as $lead) {
            $result=$atlas->recommend($lead->toArray(),$products);
            $names=implode(', ',array_column($result['products'],'name'));
            $this->line('#'.$lead->id.' | '.$lead->company.' | '.$lead->city.' | '.($lead->qualification_score??$lead->score).'/100');
            $this->line('  '.$result['purpose'].' | '.$result['evidence'].' | '.($names?:'Aucun produit correspondant en stock'));
            $this->line('  -> '.$result['next_action']);
        }
        if ($leads->isEmpty()) $this->warn('No candidates. Configure an authorized source first; ATLAS never fabricates businesses.');
        return self::SUCCESS;
    }
}
