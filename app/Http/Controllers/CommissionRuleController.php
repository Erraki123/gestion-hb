<?php

namespace App\Http\Controllers;

use App\Models\CommissionRule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommissionRuleController extends Controller
{
    public function index()
    {
        $commerciaux = User::where('role', 'commercial')->orderBy('name')->get();
        $rules = CommissionRule::whereNull('ends_at')->get()->keyBy('user_id');

        return view('commission-rules.index', compact('commerciaux', 'rules'));
    }

    public function show(User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);

        $current = CommissionRule::currentFor($commercial->id);
        $history = CommissionRule::with('creator')->where('user_id', $commercial->id)->latest('id')->get();

        return view('commission-rules.show', compact('commercial', 'current', 'history'));
    }

    public function store(Request $request, User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);

        $data = $request->validate([
            'method' => ['required', 'in:payments_percent,invoice_percent,fixed_per_sale'],
            'value'  => ['required', 'numeric', 'min:0.01', 'max:99999999'],
        ]);

        if ($data['method'] !== 'fixed_per_sale' && (float) $data['value'] > 100) {
            throw ValidationException::withMessages(['value' => 'Le pourcentage ne peut pas dépasser 100.']);
        }

        DB::transaction(function () use ($data, $commercial) {
            $current = CommissionRule::where('user_id', $commercial->id)
                ->whereNull('ends_at')->lockForUpdate()->first();

            if ($current && $current->method === $data['method'] && (float) $current->value === (float) $data['value']) {
                throw ValidationException::withMessages(['value' => 'Cette règle est déjà celle en vigueur.']);
            }

            $now = now();
            $current?->update(['ends_at' => $now]);

            CommissionRule::create([
                'user_id'    => $commercial->id,
                'method'     => $data['method'],
                'value'      => $data['value'],
                'starts_at'  => $now,
                'created_by' => auth()->id(),
            ]);
        });

        return redirect()->route('commission-rules.show', $commercial)
            ->with('success', 'Nouvelle règle appliquée aux prochaines opérations. L\'historique reste inchangé.');
    }
}