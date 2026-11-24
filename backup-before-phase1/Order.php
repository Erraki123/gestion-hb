<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\LogsActivity;
class Order extends Model
{
    use SoftDeletes , LogsActivity;

    protected $fillable = [
        'number', 'quote_id', 'client_id', 'commercial_id', 'status', 'stock_deducted',
        'subtotal_ht', 'tax_rate', 'tax_amount', 'total_ttc', 'notes',
        'approval_status', 'approval_requested_by', 'approval_requested_at',
        'approval_decided_by', 'approval_decided_at', 'approval_comment',
    ];

    protected function casts(): array
    {
        return ['stock_deducted' => 'boolean', 'approval_requested_at' => 'datetime', 'approval_decided_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $builder) {
            $user = auth()->user();
            if ($user && $user->role === 'commercial') {
                $builder->where('orders.commercial_id', $user->id);
            }
        });
    }

    public function client()     { return $this->belongsTo(Client::class)->withTrashed(); }
    public function commercial() { return $this->belongsTo(User::class, 'commercial_id')->withTrashed(); }
    public function quote()      { return $this->belongsTo(Quote::class)->withTrashed(); }
    public function items()      { return $this->hasMany(OrderItem::class); }
    public function deliveries() { return $this->hasMany(Delivery::class); }
    public function invoice()
{
    return $this->hasOne(Invoice::class)
        ->where('type', 'facture')
        ->where('status', '!=', 'annulee');
}

    public function deliveryStatus(): string
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        if ($items->isEmpty()) {
            return 'non_livree';
        }

        $remaining = $items->sum(fn ($item) => (float) $item->remainingQuantity());

        if ($remaining <= 0) {
            return 'livree';
        }

        return $items->contains(fn ($item) => (float) $item->deliveredQuantity() > 0) ? 'partielle' : 'non_livree';
    }
}
