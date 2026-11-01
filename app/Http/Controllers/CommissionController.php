<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\CommissionPayout;
use App\Models\User;
use App\Services\DocumentNumberService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommissionController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin = auth()->user()->isAdmin();
        $userFilter = $isAdmin ? $request->commercial_id : null;

        $scope = Commission::query()->when($userFilter, fn ($q, $id) => $q->where('user_id', $id));

        $stats = [
            'pending' => (clone $scope)->where('status', 'en_attente')->sum('amount'),
            'paid'    => (clone $scope)->where('status', 'versee')->sum('amount'),
        ];
        $stats['total'] = $stats['pending'] + $stats['paid'];

        $commissions = Commission::with(['user', 'invoice', 'payment'])
            ->when($userFilter, fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->from, fn ($q, $d) => $q->whereDate('computed_at', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('computed_at', '<=', $d))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $summary = collect();
        $commerciaux = collect();

        if ($isAdmin) {
            $summary = Commission::with('user')
                ->where('status', 'en_attente')
                ->selectRaw('user_id, SUM(amount) as total, COUNT(*) as n')
                ->groupBy('user_id')->get();

            $commerciaux = User::where('role', 'commercial')->orderBy('name')->get();
        }

        return view('commissions.index', compact('commissions', 'stats', 'summary', 'commerciaux'));
    }

    public function payouts()
    {
        $payouts = CommissionPayout::with('user')->latest('id')->paginate(20);

        return view('commissions.payouts', compact('payouts'));
    }

    public function payoutShow(CommissionPayout $payout)
    {
        $payout->load(['user', 'validator', 'commissions.invoice', 'commissions.payment']);

        return view('commissions.payout-show', compact('payout'));
    }

    public function payoutPdf(CommissionPayout $payout)
    {
        $payout->load(['user', 'validator', 'commissions.invoice', 'commissions.payment']);

        return Pdf::loadView('commissions.payout-pdf', compact('payout'))
            ->setPaper('a4')->stream($payout->number . '.pdf');
    }

    public function payoutCreate(Request $request)
    {
        $userId = $request->validate(['user_id' => ['required', 'integer']])['user_id'];
        $commercial = User::where('role', 'commercial')->findOrFail($userId);

        $pending = Commission::with(['invoice', 'payment'])
            ->where('user_id', $commercial->id)->where('status', 'en_attente')
            ->orderBy('id')->get();

        return view('commissions.payout-create', compact('commercial', 'pending'));
    }

    public function payoutStore(Request $request, DocumentNumberService $numbers)
    {
        $data = $request->validate([
            'user_id'          => ['required', 'integer', 'exists:users,id'],
            'commission_ids'   => ['required', 'array', 'min:1'],
            'commission_ids.*' => ['integer'],
            'paid_at'          => ['required', 'date', 'before_or_equal:today'],
            'method'           => ['required', 'in:especes,virement,cheque'],
            'reference'        => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ]);

        $ids = array_values(array_unique($data['commission_ids']));

        $payout = DB::transaction(function () use ($data, $ids, $numbers) {
            $rows = Commission::withoutGlobalScopes()
                ->whereIn('id', $ids)
                ->where('user_id', $data['user_id'])
                ->where('status', 'en_attente')
                ->lockForUpdate()->get();

            if ($rows->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    'commission_ids' => 'Certaines commissions ne sont plus disponibles. Rechargez la page.',
                ]);
            }

            $total = round((float) $rows->sum('amount'), 2);

            if ($total <= 0) {
                throw ValidationException::withMessages([
                    'commission_ids' => 'Le total à verser doit être positif.',
                ]);
            }

            $payout = CommissionPayout::create([
                'number'       => $numbers->next('CV'),
                'user_id'      => $data['user_id'],
                'amount'       => $total,
                'paid_at'      => $data['paid_at'],
                'method'       => $data['method'],
                'reference'    => $data['reference'] ?? null,
                'notes'        => $data['notes'] ?? null,
                'validated_by' => auth()->id(),
            ]);

            Commission::withoutGlobalScopes()->whereIn('id', $rows->pluck('id'))
                ->update(['status' => 'versee', 'payout_id' => $payout->id]);

            return $payout;
        });

        return redirect()->route('commissions.payouts.show', $payout)
            ->with('success', 'Versement ' . $payout->number . ' enregistré.');
    }
}