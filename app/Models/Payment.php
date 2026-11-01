<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsActivity;
class Payment extends Model
{
    use LogsActivity;

     protected $fillable = [
        'number', 'invoice_id', 'client_id', 'commercial_id', 'user_id',
        'amount', 'method', 'paid_at', 'reference',
        'cheque_number', 'cheque_bank', 'cheque_due_date', 'cheque_status',
        'status', 'cancelled_at', 'cancelled_by', 'cancel_reason', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'paid_at'         => 'date',
            'cheque_due_date' => 'date',
            'cancelled_at'    => 'datetime',
        ];
    }

    // ⭐ Le commercial ne voit que ses paiements
    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $builder) {
            $user = auth()->user();
            if ($user && $user->role === 'commercial') {
                $builder->where('payments.commercial_id', $user->id);
            }
        });
    }

    public function invoice()     { return $this->belongsTo(Invoice::class)->withTrashed(); }
    public function client()      { return $this->belongsTo(Client::class)->withTrashed(); }
    public function commercial()  { return $this->belongsTo(User::class, 'commercial_id')->withTrashed(); }
    public function user()        { return $this->belongsTo(User::class)->withTrashed(); }
    public function canceller()   { return $this->belongsTo(User::class, 'cancelled_by')->withTrashed(); }

    public function isValid(): bool { return $this->status === 'valide'; }

    public function methodLabel(): string
    {
        return ['especes' => 'Espèces', 'virement' => 'Virement', 'cheque' => 'Chèque'][$this->method] ?? $this->method;
    }
}