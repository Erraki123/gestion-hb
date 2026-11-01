<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;

class ReceivableController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::with('client')
            ->where('type', 'facture')
            ->whereIn('status', ['emise', 'partiellement_payee'])
            ->when(auth()->user()->isAdmin() && $request->commercial_id,
                fn ($q, $id) => $q->where('commercial_id', $id))
            ->get();

        $rows = $invoices->groupBy('client_id')->map(function ($group) {
            $b = ['not_due' => 0.0, 'd30' => 0.0, 'd60' => 0.0, 'd60p' => 0.0];

            foreach ($group as $inv) {
                $bal  = $inv->balance();
                $late = ($inv->due_date && $inv->due_date->lt(today()))
                    ? (int) $inv->due_date->diffInDays(today())
                    : 0;

                $key = match (true) {
                    $late === 0  => 'not_due',
                    $late <= 30  => 'd30',
                    $late <= 60  => 'd60',
                    default      => 'd60p',
                };
                $b[$key] += $bal;
            }

            return [
                'client'  => $group->first()->client,
                'count'   => $group->count(),
                'buckets' => $b,
                'total'   => array_sum($b),
            ];
        })->sortByDesc('total')->values();

        $totals = [
            'not_due' => $rows->sum('buckets.not_due'),
            'd30'     => $rows->sum('buckets.d30'),
            'd60'     => $rows->sum('buckets.d60'),
            'd60p'    => $rows->sum('buckets.d60p'),
            'total'   => $rows->sum('total'),
        ];

        $commerciaux = auth()->user()->isAdmin()
            ? User::where('role', 'commercial')->orderBy('name')->get()
            : collect();

        return view('receivables.index', compact('rows', 'totals', 'commerciaux'));
    }
}