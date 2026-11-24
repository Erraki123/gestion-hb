<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/** Small orchestrator: several restricted CRM tools, never raw SQL or autonomous writes. */
class CrmMultiAgentService
{
    public function __construct(
        private CrmAgentPlanner $planner,
        private CrmAiContextService $crm
    ) {}

    public function handle(string $question): ?string
    {
        // Phase 9: deterministic owner-scoped daily actions, no external AI or write operations.
        if (preg_match('/(?:\b(?:mes|my|nos)\s+(?:t[aâ]ches?|rappels?|priorit[eé]s|actions?)\b|\b(?:t[aâ]ches?|priorit[eé]s|actions?|rappels?)\s+(?:du jour|aujourd.hui|pour aujourd.hui)|مهامي|المهام ديالي|تذكيراتي|شنو خاصني ندير اليوم)/iu', $question)) {
            $user = auth()->user();
            if ($user && in_array($user->role, ['admin', 'commercial'], true) && $user->is_active) {
                return app(HnbAgentRoutineService::class)->answerFor($user);
            }
            return null;
        }
        $plan=$this->planner->plan($question);
        if ($plan['mode']==='external') {
            return 'La recherche externe et l’analyse des concurrents ne sont pas encore connectées. Je ne vais pas inventer de prospects. Cette fonction nécessitera des sources autorisées et une validation avant import.';
        }
        if ($plan['mode']==='action_request') {
            return 'Je peux vous aider à préparer cette opération, mais je ne l’ai pas exécutée. Les envois, changements de prix, remises et confirmations sensibles passent par les workflows autorisés du CRM.';
        }
        if (!in_array($plan['mode'],['multi','list'],true)) return null;

        $names=['clients'=>'Clients','products'=>'Produits et stock','quotes'=>'Devis','orders'=>'Commandes','invoices'=>'Factures'];
        $labels=['reference'=>'Référence','produit'=>'Produit','client'=>'Client','societe'=>'Société','ville'=>'Ville','stock'=>'Stock','seuil'=>'Seuil','prix_vente'=>'Prix vente','numero'=>'Numéro','statut'=>'Statut','total_ttc'=>'Total TTC','paye'=>'Payé'];
        $out=[];
        foreach ($plan['domains'] as $domain) {
            $response=$this->crm->fetch($domain, $plan['search']);
            $records=$response['resultats'] ?? [];
            $out[]='**'.$names[$domain].'** — '.count($records).' résultat(s) affiché(s) (maximum 12, liste non exhaustive)';
            if (!$records) {
                $out[]='Aucun résultat accessible pour cette recherche.';
                continue;
            }
            foreach (array_slice($records,0,5) as $record) {
                $fields=[];
                foreach ($record as $k=>$v) {
                    if ($k==='id' || $v===null || $v==='') continue;
                    if (is_bool($v)) $v=$v?'Oui':'Non';
                    $fields[]=($labels[$k] ?? str_replace('_',' ',$k)).' : '.$v;
                }
                $out[]='• '.implode(' · ',array_slice($fields,0,5));
            }
            if (count($records)>5) $out[]='… et '.(count($records)-5).' autre(s) dans cet aperçu.';
            $out[]='';
        }
        $out[]='Cet aperçu utilise uniquement les données que votre compte est autorisé à consulter. Pour une recherche précise, indiquez un nom ou une référence.';
        return implode("\n",$out);
    }
}
