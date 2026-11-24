<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\Quote;
use App\Models\Order;
use App\Models\Client;
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

        // Compteurs opérationnels : mêmes conditions que le centre de validation.
        // Pas de nouveau service, pas de requête IA, pas de changement financier.
        $pendingQuotes = Quote::query()
            ->where('requires_approval', true)
            ->where('approval_status', 'pending')
            ->whereIn('status', ['brouillon', 'envoye', 'accepte'])
            ->whereDoesntHave('order')
            ->count();
        $pendingOrders = Order::query()
            ->where('approval_status', 'pending')
            ->where('status', 'nouvelle')
            ->count();
        $lateProspects = Client::query()
            ->where('status', 'prospect')
            ->whereHas('prospectDetail', fn ($q) => $q
                ->where('do_not_contact', false)
                ->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<=', now())
                ->whereNotIn('stage', ['gagne', 'perdu']))
            ->count();

        return view('admin.dashboard', $data + [
            'pendingQuoteApprovals' => $pendingQuotes,
            'pendingOrderApprovals' => $pendingOrders,
            'lateProspectsCount' => $lateProspects,
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