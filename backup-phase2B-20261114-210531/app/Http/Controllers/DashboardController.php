<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function admin(Request $request, DashboardService $service)
    {
        // Filtre facultatif : voir le tableau de bord d'un commercial précis
        $cid = $request->integer('commercial_id') ?: null;

        if ($cid && ! User::where('role', 'commercial')->whereKey($cid)->exists()) {
            $cid = null;
        }

        $period = $service->period($request);
        $data   = $service->for($cid)->summary($period);

        return view('admin.dashboard', $data + [
            'period'        => $period,
            'cid'           => $cid,
            'commerciaux'   => User::where('role', 'commercial')->orderBy('name')->get(['id', 'name']),
            'perCommercial' => $service->for(null)->perCommercial($period),
        ]);
    }

    public function commercial(Request $request, DashboardService $service)
    {
        // ⭐ L'identifiant vient TOUJOURS de la session, jamais de la requête
        $period = $service->period($request);
        $data   = $service->for(auth()->id())->summary($period);

        return view('commercial.dashboard', $data + [
            'period' => $period,
            'rule'   => CommissionRule::currentFor(auth()->id()),
        ]);
    }
}