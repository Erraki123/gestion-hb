<?php

namespace App\Http\Controllers;

use App\Services\CommercialAiService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class CommercialAiController extends Controller
{
    public function index(CommercialAiService $ai): View
    {
        return view('ai.commercial', ['enabled' => $ai->enabled(), 'draft' => null]);
    }

    public function generate(Request $request, CommercialAiService $ai): View
    {
        $validated = $request->validate([
            'type' => ['required', 'in:email,whatsapp,relance'],
            'language' => ['required', 'in:fr,ar'],
            'context' => ['required', 'string', 'min:8', 'max:1200'],
        ]);

        $draft = null;
        $error = null;
        try {
            $draft = $ai->draft($validated['type'], $validated['language'], $validated['context']);
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        } catch (\Throwable $exception) {
            report($exception);
            $error = 'Le service IA est temporairement indisponible.';
        }

        return view('ai.commercial', ['enabled' => $ai->enabled(), 'draft' => $draft, 'error' => $error]);
    }
}
