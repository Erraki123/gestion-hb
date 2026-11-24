<?php
namespace App\Services;

/** Transparent advisory scores, not a trained model or a verified purchasing intent. */
final class HnbAtlasSearchScore
{
    private const FAMILIES = [
        'hotellerie'=>['acces','domotique','videosurveillance'],
        'sante'=>['acces','videosurveillance','incendie'],
        'enseignement'=>['acces','videosurveillance','incendie'],
        'commerce'=>['acces','videosurveillance','incendie'],
        'industrie'=>['acces','videosurveillance','incendie'],
        'securite'=>['acces','videosurveillance','domotique','incendie'],
    ];
    public function score(array $row, string $sector, array $availableFamilies): array
    {
        $expected=self::FAMILIES[$sector] ?? [];
        $matches=array_values(array_intersect($expected,$availableFamilies));
        $phone=(new ProspectDuplicateService())->normalizePhone($row['phone'] ?? null)!==null;
        $email=filter_var($row['email'] ?? '',FILTER_VALIDATE_EMAIL)!==false;
        $url=$row['website'] ?? '';
        $website=is_string($url) && filter_var($url,FILTER_VALIDATE_URL)!==false && in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['http','https'],true);
        // Completeness only, not authenticity or consent to contact.
        $data=($phone?35:0)+($email?35:0)+($website?20:0)+(!empty($row['city'])?10:0);
        $fit=$expected ? (int)round(100*count($matches)/count($expected)) : 0;
        $priority=(int)round(0.7*$fit+0.3*$data);
        if (($row['category'] ?? '')==='concurrent') { $priority=0; $fit=0; $matches=[]; }
        return ['priority'=>$priority,'fit'=>$fit,'completeness'=>$data,'families'=>$matches,
            'phone_present'=>$phone,'email_present'=>$email,'website_present'=>$website,
            'next_action'=>($row['category'] ?? '')==='concurrent' ? 'Veille uniquement' : (($phone||$email)?'Vérifier les coordonnées et le besoin':'Rechercher des coordonnées professionnelles autorisées')];
    }
}
