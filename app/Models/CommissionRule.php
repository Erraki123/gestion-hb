<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Concerns\LogsActivity;

class CommissionRule extends Model
{
    use LogsActivity;

    protected $fillable = ['user_id', 'method', 'value', 'starts_at', 'ends_at', 'created_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public const METHODS = [
        'payments_percent' => '% des paiements reçus',
        'invoice_percent'  => '% du montant des factures',
        'fixed_per_sale'   => 'Montant fixe par vente (facture encaissée)',
        'product_percent'  => '% selon les produits (taux général + taux spéciaux)',
    ];

    public static function currentFor(int $userId): ?self
    {
        return static::where('user_id', $userId)->whereNull('ends_at')->latest('id')->first();
    }

    public function user()         { return $this->belongsTo(User::class)->withTrashed(); }
    public function creator()      { return $this->belongsTo(User::class, 'created_by')->withTrashed(); }
    public function productRates() { return $this->hasMany(CommissionRuleProduct::class, 'rule_id'); }

    public function label(): string
    {
        $v = number_format($this->value, 2, ',', ' ');
        $n = (int) ($this->product_rates_count
            ?? ($this->relationLoaded('productRates') ? $this->productRates->count() : 0));

        return match ($this->method) {
            'payments_percent' => "$v % des paiements reçus",
            'invoice_percent'  => "$v % du montant des factures",
            'fixed_per_sale'   => "$v DH par vente encaissée",
            'product_percent'  => "$v % par produit" . ($n > 0 ? " (+ $n à taux spécial)" : ''),
            default            => $this->method,
        };
    }

    /**
     * Applique la règle d'un commercial, comme l'ancien écran « Règles commission » :
     * l'ancienne règle est fermée (jamais modifiée), une nouvelle est créée ; les commissions
     * déjà calculées ne changent pas. $method = null => « aucune commission ».
     * Retourne true si quelque chose a changé, false si la règle était déjà identique.
     */
    public static function applyFor(User $commercial, ?string $method, ?float $value, array $productRates, int $createdBy): bool
    {
        return DB::transaction(function () use ($commercial, $method, $value, $productRates, $createdBy) {
            $current = static::where('user_id', $commercial->id)->whereNull('ends_at')->lockForUpdate()->first();

            if ($method === null) {
                if (! $current) {
                    return false;
                }

                $current->update(['ends_at' => now()]);

                return true;
            }

            // Taux spéciaux : seulement pour la méthode « selon les produits » (un seul taux par produit)
            $rates = $method === 'product_percent'
                ? collect($productRates)->mapWithKeys(fn ($r) => [(int) $r['product_id'] => round((float) $r['rate'], 2)])->sortKeys()
                : collect();

            if ($current
                && $current->method === $method
                && (float) $current->value === (float) $value
                && $current->productRates()->pluck('rate', 'product_id')
                        ->map(fn ($r) => round((float) $r, 2))->sortKeys()->all() == $rates->all()) {
                return false;
            }

            $now = now();
            $current?->update(['ends_at' => $now]);

            $rule = static::create([
                'user_id'    => $commercial->id,
                'method'     => $method,
                'value'      => $value,
                'starts_at'  => $now,
                'created_by' => $createdBy,
            ]);

            foreach ($rates as $productId => $rate) {
                $rule->productRates()->create(['product_id' => $productId, 'rate' => $rate]);
            }

            return true;
        });
    }
}
