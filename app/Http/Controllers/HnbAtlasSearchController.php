<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\AtlasOpportunityService;
use App\Services\HnbAtlasSearchScore;
use App\Services\ProspectDiscoveryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class HnbAtlasSearchController extends Controller
{
    private function options(): array
    {
        return ['cities'=>array_keys(ProspectDiscoveryService::CITIES),'sectors'=>[
            'hotellerie'=>'Hôtellerie','sante'=>'Santé','enseignement'=>'Enseignement','industrie'=>'Industrie','commerce'=>'Commerce','securite'=>'Sécurité',
        ],'enabled'=>(bool)config('prospect_discovery.enabled') && (bool)config('prospect_discovery.osm_enabled')];
    }
    public function index()
    {
        return view('prospects.atlas-search',$this->options()+['report'=>null,'rows'=>[],'city'=>'Casablanca','sector'=>'hotellerie']);
    }
    public function search(Request $request, ProspectDiscoveryService $discovery, HnbAtlasSearchScore $scoring, AtlasOpportunityService $opportunity)
    {
        $data=$request->validate(['city'=>['required',Rule::in(array_keys(ProspectDiscoveryService::CITIES))],
            'sector'=>['required',Rule::in(ProspectDiscoveryService::SECTORS)],'mode'=>['required',Rule::in(['preview','review'])]]);
        $save=$data['mode']==='review';
        abort_if($save && !$request->user()->isAdmin(),403);
        $report=$discovery->discover(!$save,$data['city'],$data['sector'],true);
        $families=[];
        // Only names of available active products; no customer data or cost prices.
        foreach (Product::query()->where('is_active',true)->where('stock_quantity','>',0)->select(['id','name'])->lazyById(250) as $product) {
            $family=$opportunity->productFamily($product->name);
            if ($family!==null) $families[$family]=true;
        }
        $rows=[];
        foreach ($report['results'] ?? [] as $row) $rows[]=$row+['analysis'=>$scoring->score($row,$data['sector'],array_keys($families))];
        unset($report['results']);
        usort($rows,static fn($a,$b)=>$b['analysis']['priority']<=>$a['analysis']['priority']);
        return view('prospects.atlas-search',$this->options()+['report'=>$report,'rows'=>$rows,'city'=>$data['city'],'sector'=>$data['sector'],'saved'=>$save]);
    }
}
