<?php
namespace App\Services;
use App\Models\DiscoveredProspect;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ProspectDiscoveryService {
    public const CITIES = [
        'Casablanca'=>[33.5731,-7.5898], 'Rabat'=>[34.0209,-6.8416],
        'Marrakech'=>[31.6295,-7.9811], 'Tanger'=>[35.7595,-5.8340],
        'Fes'=>[34.0640,-4.9730], 'Agadir'=>[30.4278,-9.5981],
    ];
    // Only narrow, professional-business metadata; no scraping social profiles.
    public function overpassQuery(float $lat, float $lon): string {
        $center = sprintf('%.4f,%.4f', $lat, $lon);
        return '[out:json][timeout:25];(nwr(around:9000,'.$center.')["shop"="security"];nwr(around:9000,'.$center.')["name"~"sécurité|security|surveillance|alarme|domotique|vidéosurveillance",i];);out center 90;';
    }
    public function normalizeOsm(array $element, string $city): ?array {
        $tags = $element['tags'] ?? [];
        $name = trim((string)($tags['name'] ?? ''));
        if (mb_strlen($name)<3 || !in_array($element['type']??'', ['node','way','relation'], true) || !isset($element['id'])) return null;
        $website = trim((string)($tags['contact:website'] ?? $tags['website'] ?? ''));
        $phone = trim((string)($tags['contact:phone'] ?? $tags['phone'] ?? ''));
        $email = trim((string)($tags['contact:email'] ?? $tags['email'] ?? ''));
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $email = '';
        if ($website && !filter_var($website, FILTER_VALIDATE_URL)) $website='';
        $type = preg_match('/vente|revendeur|distributeur|wholesale/i', $name.' '.($tags['description']??''))?'revendeur':'installateur';
        return [
            'source'=>'openstreetmap', 'source_id'=>$element['type'].':'.$element['id'],
            'company'=>mb_substr($name,0,255),'city'=>$city,'category'=>$type,
            'phone'=>mb_substr($phone,0,50) ?: null, 'email'=>$email ?: null,
            'website'=>mb_substr($website,0,500) ?: null,
            'source_url'=>'https://www.openstreetmap.org/'.$element['type'].'/'.$element['id'],
            'score'=>min(95,30+(!empty($website)?15:0)+(!empty($phone)?20:0)+(!empty($email)?20:0)),
        ];
    }
    public function normalizeFeed(array $entry, string $source): ?array {
        $name=trim((string)($entry['company']??''));
        $id=trim((string)($entry['id']??''));
        if (mb_strlen($name)<3 || $id==='' || mb_strlen($id)>180) return null;
        $email=trim((string)($entry['email']??''));
        $site=trim((string)($entry['website']??''));
        if ($email && !filter_var($email,FILTER_VALIDATE_EMAIL)) $email='';
        if ($site && !filter_var($site,FILTER_VALIDATE_URL)) $site='';
        $cat=in_array($entry['category']??'', ['installateur','revendeur','client_final','concurrent'],true)?$entry['category']:'installateur';
        $sourceUrl=trim((string)($entry['source_url']??''));
        if ($sourceUrl && (!filter_var($sourceUrl,FILTER_VALIDATE_URL) || !str_starts_with(strtolower($sourceUrl),'https://'))) $sourceUrl='';
        return ['source'=>$source,'source_id'=>$id,'company'=>mb_substr($name,0,255),
            'city'=>mb_substr((string)($entry['city']??''),0,100)?:null,'category'=>$cat,
            'phone'=>mb_substr((string)($entry['phone']??''),0,50)?:null,'email'=>$email?:null,
            'website'=>mb_substr($site,0,500)?:null,'source_url'=>mb_substr($sourceUrl,0,700)?:null,
            'score'=>min(95,30+($site?15:0)+(!empty($entry['phone'])?20:0)+($email?20:0))];
    }
    public function discover(bool $dryRun=false): array {
        $report=['new'=>0,'existing'=>0,'invalid'=>0,'errors'=>[],'city'=>null];
        if (!config('prospect_discovery.enabled')) { $report['errors'][]='Discovery disabled. No network calls.'; return $report; }
        $items=[];
        if (config('prospect_discovery.osm_enabled')) {
            $cities=array_keys(self::CITIES);
            // Rotate ONE city per day, avoid systematically harvesting all of Morocco.
            $city=$cities[((int)now()->format('z'))%count($cities)];
            $report['city']=$city;
            [$lat,$lon]=self::CITIES[$city];
            try {
                $res=Http::timeout(35)->withHeaders(['User-Agent'=>config('prospect_discovery.user_agent')])
                    ->asForm()->post(config('prospect_discovery.overpass_url'),['data'=>$this->overpassQuery($lat,$lon)]);
                if (!$res->successful()) throw new RuntimeException('Overpass HTTP '.$res->status());
                foreach (array_slice($res->json('elements',[]),0,90) as $element) {
                    $row=$this->normalizeOsm($element,$city);
                    if ($row) $items[]=$row; else $report['invalid']++;
                }
            } catch (\Throwable $e) { Log::warning('Discovery OSM failed',['reason'=>$e->getMessage()]); $report['errors'][]='Source OSM unavailable'; }
        }
        foreach (config('prospect_discovery.approved_feeds',[]) as $name=>$url) {
            // Approved, configured HTTPS feeds only. Never accept arbitrary URLs from users or AI.
            if (!preg_match('/^[a-z0-9_-]{2,40}$/i',(string)$name) || !str_starts_with((string)$url,'https://')) { $report['errors'][]='Invalid feed configuration'; continue; }
            try {
                $res=Http::timeout(20)->acceptJson()->withHeaders(['User-Agent'=>config('prospect_discovery.user_agent')])->get($url);
                if (!$res->successful()) throw new RuntimeException('Feed HTTP '.$res->status());
                foreach (array_slice($res->json('companies',[]),0,100) as $entry) {
                    if (!is_array($entry)) continue;
                    $row=$this->normalizeFeed($entry,(string)$name);
                    if ($row) $items[]=$row; else $report['invalid']++;
                }
            } catch (\Throwable $e) { Log::warning('Discovery feed failed',['feed'=>$name,'reason'=>$e->getMessage()]); $report['errors'][]='Feed '.$name.' unavailable'; }
        }
        $items=array_slice($items,0,(int)config('prospect_discovery.max_candidates_per_run',60));
        foreach ($items as $row) {
            $exists=DiscoveredProspect::where('source',$row['source'])->where('source_id',$row['source_id'])->exists();
            if ($exists) { $report['existing']++; continue; }
            if (!$dryRun) DiscoveredProspect::create($row+['status'=>'pending','discovered_at'=>now()]);
            $report['new']++;
        }
        return $report;
    }
}
