<?php

namespace App\Services;

/**
 * Advisory matching only. Sectors inferred from business names are hypotheses,
 * not verified company activities or confirmed purchasing needs.
 */
class AtlasOpportunityService
{
    private const KEYWORDS = [
        'videosurveillance' => ['camera', 'cctv', 'video', 'surveillance', 'nvr', 'dvr'],
        'acces' => ['acces', 'access', 'biometr', 'empreinte', 'badge', 'zkteco', 'barriere', 'portique'],
        'domotique' => ['domotique', 'smart', 'automation', 'automatisation', 'g4'],
        'incendie' => ['incendie', 'fire', 'detection', 'alarme'],
    ];

    private const SECTOR_FAMILIES = [
        'sante' => ['acces', 'videosurveillance', 'incendie'],
        'hotellerie' => ['acces', 'domotique', 'videosurveillance'],
        'enseignement' => ['acces', 'videosurveillance', 'incendie'],
        'banque' => ['acces', 'videosurveillance'],
        'commerce' => ['videosurveillance', 'acces', 'incendie'],
        'industrie' => ['acces', 'videosurveillance', 'incendie'],
        'securite_partenaire' => ['acces', 'videosurveillance', 'incendie'],
    ];

    private function normalized(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        // Explicit substitutions before iconv: Windows locale transliteration may yield '?'
        // for accented French letters, silently breaking sector and product matching.
        $value = strtr($value, [
            'à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ã'=>'a',
            'ç'=>'c','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'î'=>'i','ï'=>'i','í'=>'i','ì'=>'i',
            'ô'=>'o','ö'=>'o','ó'=>'o','ò'=>'o','õ'=>'o',
            'ù'=>'u','û'=>'u','ü'=>'u','ú'=>'u',
            'ÿ'=>'y','ñ'=>'n','œ'=>'oe','æ'=>'ae',
        ]);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        return preg_replace('/[^a-z0-9 ]+/u', ' ', $ascii === false ? $value : strtolower($ascii)) ?? '';
    }

    private function hasWord(string $text, string $pattern): bool
    {
        return preg_match('/(?:^|\s)(?:' . $pattern . ')(?:\s|$)/u', $text) === 1;
    }

    /** @return array{sector:string,label:string,evidence:string,confidence:string} */
    public function detectSector(string $name): array
    {
        $text = $this->normalized($name);
        // Ordering matters: hotel/tech words disambiguate generic "laboratoire".
        $rules = [
            ['hotellerie', 'Hôtellerie', 'hotel|hostel|auberge|riad|resort|hospitality|maison d hotes|guesthouse|spa hotel'],
            ['securite_partenaire', 'Partenaire sécurité potentiel', 'securite|security|videosurveillance|cctv|gardiennage|alarme'],
            ['industrie', 'Industrie / technique', 'industrie|industriel|usine|fabrication|engineering|ingenierie|systemes|conception|technologie|technique'],
            ['sante', 'Santé', 'clinique|polyclinique|hopital|hospital|medical|centre de sante|pharmacie|laboratoire medical|laboratoire d analyses'],
            ['enseignement', 'Enseignement', 'ecole|school|universite|faculte|lycee|college|campus|institut'],
            ['banque', 'Banque / finance', 'banque|bank|bancaire|credit'],
            ['commerce', 'Commerce', 'supermarche|marjane|hypermarch|centre commercial|mall|magasin|market'],
        ];
        foreach ($rules as [$key, $label, $regex]) {
            if ($this->hasWord($text, $regex)) {
                return ['sector' => $key, 'label' => $label, 'evidence' => 'Indice dans le nom : ' . $name, 'confidence' => 'Faible — activité à vérifier'];
            }
        }
        return ['sector' => 'non_determine', 'label' => 'Secteur non déterminé', 'evidence' => 'Aucun indice sectoriel fiable dans le nom', 'confidence' => 'Insuffisante'];
    }

    public function productFamily(string $name): ?string
    {
        $text = $this->normalized($name);
        foreach (self::KEYWORDS as $family => $keywords) {
            foreach ($keywords as $word) {
                if (str_contains($text, $word)) return $family;
            }
        }
        return null;
    }

    /** @return array<string,mixed> */
    public function recommend(array $candidate, iterable $products): array
    {
        $category = (string) ($candidate['category'] ?? '');
        $sector = $this->detectSector((string) ($candidate['company'] ?? ''));
        $purpose = match ($category) {
            'installateur' => 'Partenariat technique potentiel',
            'revendeur' => 'Distribution potentielle',
            'client_final' => 'Besoin client à confirmer',
            'concurrent' => 'Veille concurrentielle uniquement',
            default => 'Qualification requise',
        };
        $desired = self::SECTOR_FAMILIES[$sector['sector']] ?? [];
        $matches = [];
        foreach ($products as $product) {
            $family = $this->productFamily((string) ($product->name ?? ''));
            $available = (bool) ($product->is_active ?? true) && (float) ($product->stock_quantity ?? 0) > 0;
            if ($family === null || !$available) continue;
            // Preserve 6D behavior for unknown sectors: available catalog items
            // are generic suggestions, clearly marked non-relevant.
            if ($desired !== [] && !in_array($family, $desired, true)) continue;
            $matches[] = [
                'id' => $product->id ?? null,
                'name' => (string) $product->name,
                'family' => $family,
                'relevant' => $desired !== [],
            ];
        }
        $matches = array_slice($matches, 0, 3);
        $ready = $category !== 'concurrent' && (!empty($candidate['phone']) || !empty($candidate['email']));
        $contactFields = (int) !empty($candidate['phone']) + (int) !empty($candidate['email']) + (int) !empty($candidate['website']);
        $dataQuality = match (true) {
            $contactFields >= 2 => 'Moyenne — vérifier les coordonnées',
            $contactFields === 1 => 'Faible — contact partiel',
            default => 'Très faible — aucun contact direct',
        };
        return [
            'purpose' => $purpose,
            'evidence' => $sector['evidence'],
            'sector' => $sector['sector'],
            'sector_label' => $sector['label'],
            'sector_confidence' => $sector['confidence'],
            'data_quality' => $dataQuality,
            'suggested_families' => $category === 'concurrent' ? [] : $desired,
            'products' => $category === 'concurrent' ? [] : $matches,
            'contact_ready' => $ready,
            'next_action' => $category === 'concurrent'
                ? 'Comparer uniquement les offres publiques et vérifier les sources'
                : ($ready ? 'Valider le secteur et le besoin avant une offre' : 'Vérifier le secteur et les coordonnées professionnelles'),
        ];
    }
}
