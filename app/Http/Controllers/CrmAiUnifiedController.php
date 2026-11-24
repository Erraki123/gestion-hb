<?php

namespace App\Http\Controllers;

use App\Services\CommercialAiService;
use App\Services\CrmAiAnswerService;
use App\Services\CrmAiContextService;
use App\Services\CrmAiIntentService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CrmAiUnifiedController extends Controller
{
    public function index(): View
    {
        return view('ai.unified', ['answer'=>null, 'error'=>null, 'question'=>'']);
    }

    public function ask(Request $request, CrmAiIntentService $intent, CrmAiContextService $crm, CrmAiAnswerService $answerService, CommercialAiService $draftService): View
    {
        $data=$request->validate(['question'=>['required','string','min:5','max:500'],'search'=>['nullable','string','max:100']]);
        $question=trim($data['question']);
        $identified=$intent->detect($question);
        $answer=null;
        $error=null;
        try {
            if ($identified['mode']==='draft') {
                $language=preg_match('/[\x{0600}-\x{06FF}]/u',$question) ? 'ar' : 'fr';
                $answer=$draftService->draft($identified['type'], $language, $question);
            } elseif ($identified['mode']==='clarify') {
                $answer='Précisez une seule catégorie (produit/stock, client, devis, commande ou facture) et une référence précise, par exemple : Quel est le stock du produit "CAM-001" ?';
            } else {
                $search=trim($data['search'] ?? $identified['search']);
                if ($search==='') {
                    $answer='Indiquez le nom exact ou la référence dans le champ « Nom / référence ». Exemple : CAM-001. Aucun dossier n’a été consulté.';
                } else {
                    $records=$crm->fetch($identified['domain'], $search);
                    if (empty($records['resultats'])) {
                        $answer='Aucun résultat autorisé trouvé pour « '.$search.' » dans '. $identified['domain'].'. Vérifiez la référence ou votre accès.';
                    } else {
                        $answer=$answerService->answer($question,$records);
                    }
                }
            }
        } catch (Throwable $e) {
            report($e);
            $error='Impossible de traiter la demande pour le moment. Vérifiez la configuration IA ou réessayez.';
        }
        return view('ai.unified', compact('answer','error','question'));
    }
}
