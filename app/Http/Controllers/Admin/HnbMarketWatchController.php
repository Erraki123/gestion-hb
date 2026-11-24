<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\HnbMarketPriceComparison;
use App\Services\ProspectDiscoveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class HnbMarketWatchController extends Controller
{
    private function page(?array $search = null, string $city = 'Casablanca')
    {
        return view('prospects.market-watch', [
            'cities'=>array_keys(ProspectDiscoveryService::CITIES),'city'=>$city,'search'=>$search,
            'enabled'=>(bool)config('prospect_discovery.enabled') && (bool)config('prospect_discovery.osm_enabled'),
            'observations'=>DB::table('hnb_market_observations')->orderByDesc('id')->paginate(20),
        ]);
    }

    public function index() { return $this->page(); }

    public function search(Request $request, ProspectDiscoveryService $discovery)
    {
        $data=$request->validate(['city'=>['required',Rule::in(array_keys(ProspectDiscoveryService::CITIES))]]);
        // Preview only. OSM business names are clues, not confirmed competitor identities.
        $report=$discovery->discover(true,$data['city'],'securite',true);
        $rows=[];
        foreach ($report['results'] ?? [] as $row) {
            $url=(string)($row['source_url']??'');
            if (!preg_match('~^https://www\\.openstreetmap\\.org/(node|way|relation)/[0-9]+$~D',$url)) continue;
            $rows[]=['company'=>(string)$row['company'],'source_url'=>$url];
        }
        return $this->page(['rows'=>$rows,'errors'=>$report['errors']??[],'checked_at'=>now()->format('d/m/Y H:i')],$data['city']);
    }

    public function store(Request $request, HnbMarketPriceComparison $comparison)
    {
        $data=$request->validate([
            'company'=>['required','string','max:180'], 'city'=>['required',Rule::in(array_keys(ProspectDiscoveryService::CITIES))],
            'sku'=>['required','string','max:60'], 'basis'=>['required',Rule::in(['HT','TTC'])],
            'public_price'=>['required','numeric','min:0.01','max:9999999999.99','decimal:0,2'],
            'source_url'=>['required','string','max:1000','url:https'],
            'observed_on'=>['required','date_format:Y-m-d','before_or_equal:today'],
            'same_reference'=>['accepted'], 'same_unit'=>['accepted'], 'source_checked'=>['accepted'],
        ]);
        $parts=parse_url($data['source_url']);
        if (!$parts || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            throw ValidationException::withMessages(['source_url'=>'Lien HTTPS public sans identifiants requis.']);
        }
        $product=Product::query()->where('is_active',true)->where('sku',trim($data['sku']))->first();
        if (!$product || strcasecmp((string)$product->sku,trim($data['sku']))!==0) {
            throw ValidationException::withMessages(['sku'=>'Référence exacte introuvable dans le catalogue actif.']);
        }
        try { $prices=$comparison->compare($product,$data['basis'],(float)$data['public_price']); }
        catch (\InvalidArgumentException $e) { throw ValidationException::withMessages(['public_price'=>$e->getMessage()]); }
        // No fetch of submitted URLs, no changes to product prices, no contact or AI call.
        DB::table('hnb_market_observations')->insert([
            'product_id'=>$product->id,'user_id'=>$request->user()->id,'company'=>$data['company'],'city'=>$data['city'],
            'sku'=>$product->sku,'product_name'=>$product->name,'unit'=>$product->unit,'basis'=>$data['basis'],
            'public_price'=>$prices['public_price'],'catalog_price'=>$prices['catalog_price'],'source_url'=>$data['source_url'],
            'observed_on'=>$data['observed_on'],'created_at'=>now(),'updated_at'=>now(),
        ]);
        return redirect()->route('admin.market-watch.index')->with('market_message','Observation enregistrée. Prix source déclaré par le directeur, non contrôlé automatiquement.');
    }
}
