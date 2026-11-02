<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CustomerReturn extends Model
{
    use LogsActivity;

    protected $fillable = [
        'number', 'invoice_id', 'client_id', 'commercial_id', 'status', 'return_date',
        'reason', 'notes', 'credit_note_id', 'validated_at', 'validated_by',
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'validated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $builder) {
            $user = auth()->user();
            if ($user && $user->role === 'commercial') {
                $builder->where('customer_returns.commercial_id', $user->id);
            }
        });
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class)->withTrashed();
    }

    public function client()
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function commercial()
    {
        return $this->belongsTo(User::class, 'commercial_id')->withTrashed();
    }

    public function items()
    {
        return $this->hasMany(CustomerReturnItem::class);
    }

    public function creditNote()
    {
        return $this->belongsTo(Invoice::class, 'credit_note_id')->withTrashed();
    }

    public function displayNumber(): string
    {
        return $this->number ?? 'Brouillon #' . $this->id;
    }
}
