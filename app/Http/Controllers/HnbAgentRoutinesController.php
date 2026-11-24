<?php

namespace App\Http\Controllers;

use App\Services\HnbAgentRoutineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HnbAgentRoutinesController extends Controller
{
    public function index(Request $request, HnbAgentRoutineService $agent)
    {
        $items = $agent->withSnoozes($request->user());
        $showAll = $request->query('show') === 'all';
        $visible = array_filter($items, fn ($item) => $showAll || ! $item['snoozed']);
        $lastScan = DB::table('hnb_agent_routine_runs')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('run_date')->first();
        return response()->view('assistant/routines', compact('items', 'visible', 'showAll', 'lastScan'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function snooze(Request $request, HnbAgentRoutineService $agent)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:90', 'regex:/^[a-z_]+:[0-9]+$/'],
            'fingerprint' => ['required', 'regex:/^[a-f0-9]{64}$/'],
            'hours' => ['required', 'integer', 'in:0,24,72,168'],
        ]);
        if (! $agent->snooze($request->user(), $data['key'], $data['fingerprint'], (int) $data['hours'])) {
            abort(409, 'Rappel modifié, résolu ou inaccessible. Actualisez la page.');
        }
        return redirect()->route('hnb.routines.index')->with('success',
            (int) $data['hours'] === 0 ? 'Rappel réactivé.' : 'Rappel reporté sans aucune opération commerciale.');
    }
}
