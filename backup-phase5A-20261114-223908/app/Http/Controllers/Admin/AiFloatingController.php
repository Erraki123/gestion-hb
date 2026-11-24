<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CrmAiUnifiedController;
use App\Services\CommercialAiService;
use App\Services\CrmAiAnswerService;
use App\Services\CrmAiContextService;
use App\Services\CrmAiIntentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Reuse the existing Phase 4C assistant; never query unrestricted SQL. */
class AiFloatingController extends Controller
{
    public function ask(
        Request $request,
        CrmAiUnifiedController $assistant,
        CrmAiIntentService $intent,
        CrmAiContextService $crm,
        CrmAiAnswerService $answers,
        CommercialAiService $drafts
    ): JsonResponse {
        $view = $assistant->ask($request, $intent, $crm, $answers, $drafts);
        $data = $view->getData();

        return response()->json([
            'answer' => $data['answer'] ?? null,
            'error' => $data['error'] ?? null,
        ])->header('Cache-Control', 'no-store, private');
    }
}
