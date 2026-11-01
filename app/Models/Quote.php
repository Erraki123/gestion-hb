<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\LogsActivity;
class Quote extends Model
{
    use SoftDeletes , LogsActivity;

    protected $fillable = [
        'number', 'client_id', 'commercial_id', 'status', 'issue_date', 'valid_until',
        'subtotal_ht', 'tax_rate', 'tax_amount', 'total_ttc', 'terms', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date'  => 'date',
            'valid_until' => 'date',
        ];
    }

    // ⭐ Le commercial ne voit que ses devis
    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $builder) {
            $user = auth()->user();
            if ($user && $user->role === 'commercial') {
                $builder->where('quotes.commercial_id', $user->id);
            }
        });
    }

    public function client()     { return $this->belongsTo(Client::class)->withTrashed(); }
    public function commercial() { return $this->belongsTo(User::class, 'commercial_id')->withTrashed(); }
    public function items()      { return $this->hasMany(QuoteItem::class); }
    public function order()      { return $this->hasOne(Order::class); }

    public function isEditable(): bool { return $this->status === 'brouillon'; }
}