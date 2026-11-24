<?php
namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;

final class HnbMarketResearch
{
    public function sources(): array
    {
        $result=[];
        foreach ((array)config('hnb_market_research.sources',[]) as $key=>$entry) {
            if (!is_string($key) || !preg_match('/^[a-z0-9_-]{2,40}$/D',$key) || !is_array($entry)) continue;
            if (!$this->https($entry['url']??null) || !is_array($entry['offer_hosts']??null) || !$entry['offer_hosts']) continue;
            $result[$key]=$entry;
        }
        return $result;
    }

    private function https(mixed $url): bool
    {
        if (!is_string($url) || strlen($url)>1000 || !filter_var($url,FILTER_VALIDATE_URL)) return false;
        $p=parse_url($url);
        if (!$p || ($p['scheme']??'')!=='https' || isset($p['user']) || isset($p['pass']) || isset($p['fragment']) || (isset($p['port']) && $p['port']!==443)) return false;
        $host=strtolower($p['host']??'');
        // Configured DNS names only. No IP literals, localhost or arbitrary user-supplied endpoints.
        return (bool)preg_match('/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,63}$/D',$host)
            && !preg_match('/\.(?:local|internal|localhost|lan|home)$/D',$host);
    }

    public function preview(Product $product, array $keys): array
    {
        $sources=$this->sources(); $rows=[]; $errors=[]; $seen=[];
        foreach (array_slice(array_unique($keys),0,3) as $key) {
            if (!isset($sources[$key])) { $errors[]='Source non autorisée.'; continue; }
            $source=$sources[$key];
            try {
                // No redirects, no user-controlled URL, no outgoing CRM information.
                $response=Http::acceptJson()->connectTimeout(3)->timeout(7)
                    ->withOptions(['allow_redirects'=>false])->get($source['url']);
                if (!$response->successful() || strlen($response->body())>1000000) throw new \RuntimeException('Feed invalid');
                $offers=$response->json('offers');
                if (!is_array($offers) || !array_is_list($offers)) throw new \RuntimeException('Feed invalid');
                foreach (array_slice($offers,0,200) as $offer) {
                    if (!is_array($offer)) continue;
                    $row=$this->normalize($product,$offer,$source['offer_hosts']);
                    if (!$row) continue;
                    $id=hash('sha256',json_encode([$row['source_url'],$row['basis'],$row['public_price'],$row['observed_on']]));
                    if (isset($seen[$id])) continue;
                    $seen[$id]=true;
                    $rows[]=$row+['feed'=>$key,'fetched_at'=>now()->toDateTimeString()];
                    if (count($rows)>=60) break 2;
                }
            } catch (\Throwable $e) { $errors[]='Source '.$key.' indisponible ou format invalide.'; }
        }
        return ['rows'=>$rows,'errors'=>$errors];
    }

    public function normalize(Product $product,array $offer,array $hosts): ?array
    {
        foreach (['sku','company','unit','currency','basis','observed_on','source_url'] as $field) {
            if (!is_string($offer[$field]??null)) return null;
        }
        if (strcasecmp(trim($offer['sku']),(string)$product->sku)!==0 || trim($offer['unit'])!==(string)$product->unit || $offer['currency']!=='MAD') return null;
        if (trim($offer['company'])==='' || mb_strlen($offer['company'])>180 || !$this->https($offer['source_url'])) return null;
        if (!in_array(strtolower(parse_url($offer['source_url'],PHP_URL_HOST)),array_map('strtolower',array_filter($hosts,'is_string')),true)) return null;
        $date=\DateTimeImmutable::createFromFormat('!Y-m-d',$offer['observed_on']);
        if (!$date || $date->format('Y-m-d')!==$offer['observed_on'] || $offer['observed_on']>today()->toDateString()) return null;
        $price=$offer['public_price']??null;
        if ((!is_string($price) && !is_int($price) && !is_float($price)) || !preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D',(string)$price) || (float)$price<=0) return null;
        try { $prices=app(HnbMarketPriceComparison::class)->compare($product,$offer['basis'],(float)$price); }
        catch (\InvalidArgumentException) { return null; }
        return $prices+['company'=>trim($offer['company']),'sku'=>$product->sku,'unit'=>$product->unit,'basis'=>$offer['basis'],'source_url'=>$offer['source_url'],'observed_on'=>$offer['observed_on']];
    }
}
