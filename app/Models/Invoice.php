<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\LogsActivity;

class Invoice extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'number', 'type', 'parent_id', 'order_id', 'client_id', 'commercial_id', 'status',
        'issue_date', 'due_date', 'subtotal_ht', 'tax_rate', 'tax_amount',
        'total_ttc', 'amount_paid', 'terms', 'notes',
    ];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'due_date' => 'date'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $builder) {
            $user = auth()->user();
            if ($user && $user->role === 'commercial') {
                $builder->where('invoices.commercial_id', $user->id);
            }
        });
    }

    public function client()     { return $this->belongsTo(Client::class)->withTrashed(); }
    public function commercial() { return $this->belongsTo(User::class, 'commercial_id')->withTrashed(); }
    public function order()      { return $this->belongsTo(Order::class)->withTrashed(); }
    public function items()      { return $this->hasMany(InvoiceItem::class); }
    public function parent()     { return $this->belongsTo(Invoice::class, 'parent_id'); }
    public function credit()     { return $this->hasOne(Invoice::class, 'parent_id'); }
    public function payments()   { return $this->hasMany(Payment::class)->latest('id'); }

    public static function partialCredits(): Builder
    {
        return static::query()->partialCredits();
    }

    public function scopeOverdue(Builder $q): Builder
    {
        return $q->where('type', 'facture')
            ->whereIn('status', ['emise', 'partiellement_payee'])
            ->whereDate('due_date', '<', today());
    }

    public function scopePartialCredits(Builder $q): Builder
    {
        return $q->where('type', 'avoir')
            ->where('status', 'emise')
            ->whereHas('parent', fn ($parentQuery) => $parentQuery->where('status', '!=', 'annulee'));
    }

    public function isDraft(): bool { return $this->status === 'brouillon'; }

    public function creditedAmount(): float
    {
        return round((float) static::where('parent_id', $this->id)
            ->where('type', 'avoir')
            ->where('status', 'emise')
            ->sum('total_ttc'), 2);
    }

    public function balance(): float
    {
        return round((float) $this->total_ttc - (float) $this->creditedAmount() - (float) $this->amount_paid, 2);
    }

    public function creditDue(): float
    {
        return max(0.0, -$this->balance());
    }

    public function isOverdue(): bool
    {
        return $this->type === 'facture'
            && in_array($this->status, ['emise', 'partiellement_payee'], true)
            && $this->due_date
            && $this->due_date->lt(today());
    }

    public function displayStatus(): string
    {
        return $this->isOverdue() ? 'en_retard' : $this->status;
    }

    public function title(): string { return $this->type === 'avoir' ? 'Avoir' : 'Facture'; }

    public function displayNumber(): string { return $this->number ?? 'Brouillon #' . $this->id; }
}
