<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsActivity;
class CommissionPayout extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'number', 'user_id', 'amount', 'paid_at', 'method', 'reference', 'notes', 'validated_by',
    ];

    protected function casts(): array
    {
        return ['paid_at' => 'date'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $builder) {
            $user = auth()->user();
            if ($user && $user->role === 'commercial') {
                $builder->where('commission_payouts.user_id', $user->id);
            }
        });
    }

    public function user()        { return $this->belongsTo(User::class)->withTrashed(); }
    public function validator()   { return $this->belongsTo(User::class, 'validated_by')->withTrashed(); }
    public function commissions() { return $this->hasMany(Commission::class, 'payout_id'); }

    public function methodLabel(): string
    {
        return ['especes' => 'Espèces', 'virement' => 'Virement', 'cheque' => 'Chèque'][$this->method] ?? $this->method;
    }
}