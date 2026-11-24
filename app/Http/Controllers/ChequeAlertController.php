<?php

namespace App\Http\Controllers;

use App\Support\ChequeAlerts;
use Illuminate\Support\Facades\Blade;

/** Données du jour pour le 🔔 (rafraîchi toutes les 60 secondes) */
class ChequeAlertController extends Controller
{
    public function index()
    {
        $a = ChequeAlerts::upcoming();

        return response()->json([
            'total'   => $a['total'],
            'urgent'  => $a['urgent'],
            'sum_fmt' => number_format($a['sum'], 2, ',', ' '),
            'html'    => Blade::render('<x-cheque-list :items="$items" />', ['items' => $a['items']]),
        ]);
    }
}
