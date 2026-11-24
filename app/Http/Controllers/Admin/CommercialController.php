<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class CommercialController extends Controller
{
    public function index(Request $request)
    {
        $commerciaux = User::where('role', 'commercial')
            ->when($request->q, fn ($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Règle de commission en vigueur de chaque commercial (une seule requête)
        $rules = CommissionRule::whereNull('ends_at')
            ->whereIn('user_id', $commerciaux->pluck('id'))
            ->withCount('productRates')
            ->get()
            ->keyBy('user_id');

        // Commissions en attente de versement par commercial (une seule requête)
        $pending = Commission::withoutGlobalScopes()
            ->where('status', 'en_attente')
            ->whereIn('user_id', $commerciaux->pluck('id'))
            ->selectRaw('user_id, SUM(amount) as total, COUNT(*) as n')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        return view('admin.commerciaux.index', compact('commerciaux', 'rules', 'pending'));
    }

    public function create()
    {
        return view('admin.commerciaux.create', [
            'current' => null,
            'history' => collect(),
            'rates'   => collect(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name'              => $data['name'],
                'email'             => $data['email'],
                'phone'             => $data['phone'] ?? null,
                'password'          => Hash::make($data['password']),
                'role'              => 'commercial',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);

            $this->saveRule($user, $data);
        });

        return redirect()->route('admin.commerciaux.index')
            ->with('success', 'Compte commercial créé avec succès.');
    }

    public function edit(User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);

        $current = CommissionRule::currentFor($commercial->id);
        $current?->load('productRates.product');

        $history = CommissionRule::with('creator')->withCount('productRates')
            ->where('user_id', $commercial->id)->latest('id')->get();

        $rates = $current
            ? $current->productRates->map(fn ($r) => [
                'product_id' => $r->product_id,
                'label'      => $r->product?->name ?? ('Produit #' . $r->product_id),
                'rate'       => (float) $r->rate,
            ])->values()
            : collect();

        return view('admin.commerciaux.edit', compact('commercial', 'current', 'history', 'rates'));
    }

    public function update(Request $request, User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);

        $data = $this->validated($request, $commercial);

        $ruleChanged = DB::transaction(function () use ($data, $commercial) {
            $commercial->fill([
                'name'  => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ]);

            if (! empty($data['password'])) {
                $commercial->password = Hash::make($data['password']);
            }

            $commercial->save();

            return $this->saveRule($commercial, $data);
        });

        return redirect()->route('admin.commerciaux.index')
            ->with('success', 'Compte mis à jour.' . ($ruleChanged ? ' Nouvelle règle de commission appliquée aux prochaines opérations.' : ''));
    }

    public function toggle(User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);

        $commercial->update(['is_active' => ! $commercial->is_active]);

        return back()->with('success', $commercial->is_active
            ? 'Compte activé.'
            : 'Compte désactivé.');
    }

    public function destroy(User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);

        $commercial->delete(); // Soft delete : les données historiques sont conservées

        return back()->with('success', 'Compte supprimé (restaurable).');
    }

    /* ----------------- helpers ----------------- */

    private function validated(Request $request, ?User $commercial): array
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email' . ($commercial ? ',' . $commercial->id : '')],
            'phone'    => ['nullable', 'string', 'max:30'],
            'password' => [$commercial ? 'nullable' : 'required', 'confirmed', Password::min(8)],

            // Commission : facultative (vide = aucune commission)
            'method'                     => ['nullable', 'in:payments_percent,invoice_percent,fixed_per_sale,product_percent'],
            'value'                      => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'product_rates'              => ['nullable', 'array', 'max:300'],
            'product_rates.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'product_rates.*.rate'       => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $method = $data['method'] ?? null;

        if ($method) {
            $value = $data['value'] ?? null;

            if ($value === null || $value === '') {
                throw ValidationException::withMessages(['value' => 'Indiquez le taux ou le montant de la commission.']);
            }

            // Le taux général de « selon les produits » peut être 0 (seuls les produits listés rapportent)
            if ($method !== 'product_percent' && (float) $value <= 0) {
                throw ValidationException::withMessages(['value' => 'La valeur doit être supérieure à 0.']);
            }

            if ($method !== 'fixed_per_sale' && (float) $value > 100) {
                throw ValidationException::withMessages(['value' => 'Le pourcentage ne peut pas dépasser 100.']);
            }
        }

        return $data;
    }

    private function saveRule(User $user, array $data): bool
    {
        $method = ($data['method'] ?? null) ?: null;

        return CommissionRule::applyFor(
            $user,
            $method,
            $method ? (float) $data['value'] : null,
            $data['product_rates'] ?? [],
            auth()->id()
        );
    }
}
