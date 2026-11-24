<?php

namespace App\Http\Controllers;

use App\Services\CrmAiAnswerService;
use App\Services\CrmAiContextService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CrmAiQuestionController extends Controller
{
    public function index(): View
    {
        return view('ai.crm-question', ['answer'=>null,'error'=>null]);
    }

    public function ask(Request $request, CrmAiContextService $context, CrmAiAnswerService $ai): View
    {
        $data=$request->validate([
            'domain'=>['required','in:products,clients,quotes,orders,invoices'],
            'search'=>['nullable','string','max:100'],
            'question'=>['required','string','min:5','max:500'],
        ]);
        try {
            $records=$context->fetch($data['domain'], $data['search'] ?? '');
            $answer=$ai->answer($data['question'], $records);
            return view('ai.crm-question', ['answer'=>$answer,'error'=>null]);
        } catch(Throwable $e) {
            report($e);
            return view('ai.crm-question', ['answer'=>null,'error'=>'Impossible de consulter les données pour le moment.']);
        }
    }
}
