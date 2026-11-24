<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\HnbMarketResearch;
use App\Services\HnbMarketAnalysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class HnbMarketResearchController extends Controller
{
    private function page(array $data=[])
    {
        return view('prospects.market-research',$data+['sources'=>array_keys(app(HnbMarketResearch::class)->sources()),'aiEnabled'=>app(HnbMarketAnalysis::class)->enabled(),'rows'=>[],'errorsList'=>[],'observations'=>[],'analysis'=>null,'sku'=>'','searched'=>false,'reported'=>false]);
    }
    public function index() { return $this->page(); }
    public function preview(Request $request,HnbMarketResearch $research)
    {
        $data=$request->validate(['sku'=>['required','string','max:60'],'sources'=>['required','array','min:1','max:3'],'sources.*'=>['required','string','distinct',Rule::in(array_keys($research->sources()))]]);
        $sku=trim($data['sku']);
        $p=Product::query()->where('is_active',true)->where('sku',$sku)->first();
        if (!$p || strcasecmp((string)$p->sku,$sku)!==0) throw ValidationException::withMessages(['sku'=>'Référence active exacte requise.']);
        $result=$research->preview($p,$data['sources']);
        return $this->page(['rows'=>$result['rows'],'errorsList'=>$result['errors'],'sku'=>$p->sku,'searched'=>true]);
    }
    public function report(Request $request,HnbMarketAnalysis $analysis)
    {
        $data=$request->validate(['sku'=>['required','string','max:60'],'use_ai'=>['nullable','boolean']]);
        $sku=trim($data['sku']);
        $rows=DB::table('hnb_market_observations')->where('sku',$sku)->orderByDesc('observed_on')->orderByDesc('id')->limit(12)->get()->all();
        $rows=array_values(array_filter($rows,fn($row)=>strcasecmp($row->sku,$sku)===0));
        $text=null;$errors=[];
        if ($request->boolean('use_ai') && $rows) {
            $facts=[];
            foreach ($rows as $i=>$row) {
                // These are public observations entered and confirmed by the director, not independent verification.
                $facts[]=['ref'=>'O'.($i+1),'company'=>$row->company,'sku'=>$row->sku,'unit'=>$row->unit,'basis'=>$row->basis,'public_price_MAD'=>(string)$row->public_price,'observed_on'=>$row->observed_on];
            }
            try { $text=$analysis->suggest($facts); } catch (\Throwable) { $errors[]='Analyse IA indisponible. Les observations locales restent affichées.'; }
        }
        return $this->page(['sku'=>$sku,'observations'=>$rows,'analysis'=>$text,'errorsList'=>$errors,'reported'=>true]);
    }
}
