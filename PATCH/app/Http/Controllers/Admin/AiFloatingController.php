<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CrmAiUnifiedController;
use App\Services\CommercialAiService;
use App\Services\CrmAiAnswerService;
use App\Services\CrmAiContextService;
use App\Services\CrmAiIntentService;
use App\Services\CrmMultiAgentService;
use App\Services\CrmConversationContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Phase 5A: multi-domain questions first; existing specialists handle everything else. */
class AiFloatingController extends Controller
{
    public function ask(
        Request $request,
        CrmMultiAgentService $agent,
        CrmConversationContextService $conversation,
        CrmAiUnifiedController $assistant,
        CrmAiIntentService $intent,
        CrmAiContextService $crm,
        CrmAiAnswerService $answers,
        CommercialAiService $drafts
    ): JsonResponse {
        $validated=$request->validate(['question'=>['required','string','min:5','max:500']]);
        $question=trim($validated['question']);
        $answer=$conversation->handle($request, $question);
        if ($answer === null) $answer=$agent->handle($question);
        if ($answer !== null) {
            return response()->json(['answer'=>$answer,'error'=>null])
                ->header('Cache-Control','no-store, private');
        }
        $view=$assistant->ask($request,$intent,$crm,$answers,$drafts);
        $data=$view->getData();
        return response()->json(['answer'=>$data['answer'] ?? null,'error'=>$data['error'] ?? null])
            ->header('Cache-Control','no-store, private');
    }
}
