<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
    ];

    public static function currentFor(int $userId): ?self
    {
        return static::where('user_id', $userId)->whereNull('ends_at')->latest('id')->first();
    }

    public function user()    { return $this->belongsTo(User::class)->withTrashed(); }
    public function creator() { return $this->belongsTo(User::class, 'created_by')->withTrashed(); }

    public function label(): string
    {
        $v = number_format($this->value, 2, ',', ' ');

        return match ($this->method) {
            'payments_percent' => "$v % des paiements reçus",
            'invoice_percent'  => "$v % du montant des factures",
            'fixed_per_sale'   => "$v DH par vente encaissée",
            default            => $this->method,
        };
    }
}