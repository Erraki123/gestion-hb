<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsActivity;
class Commission extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'user_id', 'rule_id', 'kind', 'parent_id', 'invoice_id', 'payment_id', 'payout_id',
        'method', 'rate_value', 'base_amount', 'amount', 'status',
        'computed_at', 'cancelled_at', 'note',
    ];

    protected function casts(): array
    {
        return ['computed_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    // ⭐ Le commercial ne voit que ses commissions
    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $builder) {
            $user = auth()->user();
            if ($user && $user->role === 'commercial') {
                $builder->where('commissions.user_id', $user->id);
            }
        });
    }

    public function user()    { return $this->belongsTo(User::class)->withTrashed(); }
    public function invoice() { return $this->belongsTo(Invoice::class)->withTrashed(); }
    public function payment() { return $this->belongsTo(Payment::class); }
    public function payout()  { return $this->belongsTo(CommissionPayout::class, 'payout_id'); }

    public function ruleLabel(): string
    {
        $v = number_format($this->rate_value, 2, ',', ' ');

        return match ($this->method) {
            'payments_percent' => "$v % des paiements",
            'invoice_percent'  => "$v % de la facture",
            'fixed_per_sale'   => "$v DH par vente",
            default            => $this->method,
        };
    }

    public function sourceLabel(): string
    {
        $inv = $this->invoice?->number ?? '—';

        return match ($this->kind) {
            'payment'    => 'Paiement ' . ($this->payment?->number ?? '') . ' · ' . $inv,
            'invoice'    => 'Facture ' . $inv,
            'fixed'      => 'Vente encaissée · ' . $inv,
            'ajustement' => 'Ajustement · ' . $inv,
            default      => $inv,
        };
    }
}