<?php
namespace App\Services;

use App\Models\DiscoveredProspect;
use App\Models\Product;
use Illuminate\Support\Collection;

/** Evidence-bound recommendations, no inferred customer need or automatic outreach. */
class AtlasOpportunityService
{
    private const KEYWORDS = [
        'videosurveillance' => ['camera','caméra','cctv','video','vidéo','surveillance','nvr','dvr'],
        'acces' => ['acces','accès','access','biometr','biométr','empreinte','badge','zkteco','barriere','barrière','portique'],
        'domotique' => ['domotique','smart','automation','automatisation','g4','maison','hotel','hôtel'],
        'incendie' => ['incendie','fire','détection','detection','alarme'],
    ];

    public function productFamily(string $name): ?string
    {
        $text=mb_strtolower($name);
        foreach (self::KEYWORDS as $family=>$keywords) {
            foreach ($keywords as $word) {
                if (mb_strpos($text,$word)!==false) return $family;
            }
        }
        return null;
    }

    /** @return array<string, mixed> */
    public function recommend(array $candidate, iterable $products): array
    {
        $category=(string)($candidate['category']??'');
        $company=mb_strtolower((string)($candidate['company']??''));
        $purpose=match ($category) {
            'installateur'=>'Partenariat technique potentiel',
            'revendeur'=>'Distribution potentielle',
            'client_final'=>'Besoin client à confirmer',
            'concurrent'=>'Veille concurrentielle uniquement',
            default=>'Qualification requise',
        };
        $hints=[];
        foreach (self::KEYWORDS as $family=>$words) {
            foreach ($words as $word) {
                if (mb_strpos($company,$word)!==false) { $hints[]=$family; break; }
            }
        }
        $matches=[];
        foreach ($products as $product) {
            $family=$this->productFamily((string)($product->name??''));
            if ($family===null) continue;
            $available=(bool)($product->is_active??true) && (float)($product->stock_quantity??0)>0;
            if (!$available) continue;
            $matches[]=['id'=>$product->id??null,'name'=>(string)$product->name,'family'=>$family,'relevant'=>in_array($family,$hints,true)];
        }
        usort($matches,fn($a,$b)=>(int)$b['relevant']<=>(int)$a['relevant']);
        $matches=array_slice($matches,0,3);
        $confidence=count($hints)>0?'Indice sectoriel dans le nom':'Secteur non confirmé';
        $ready=($category!=='concurrent' && (!empty($candidate['phone']) || !empty($candidate['email'])));
        return [
            'purpose'=>$purpose,'evidence'=>$confidence,
            'products'=>$category==='concurrent'?[]:$matches,
            'contact_ready'=>$ready,
            'next_action'=>$category==='concurrent'?'Comparer les offres publiques et vérifier les sources':
                ($ready?'Vérifier le besoin avant de proposer un devis':'Vérifier les coordonnées professionnelles'),
        ];
    }
}
