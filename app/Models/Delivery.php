<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use LogsActivity;

    protected $fillable = [
        'number', 'order_id', 'client_id', 'commercial_id', 'status', 'delivery_date',
        'received_by', 'notes', 'stock_moved', 'validated_at', 'validated_by',
        'cancelled_at', 'cancelled_by', 'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'stock_moved' => 'boolean',
            'validated_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $builder) {
            $user = auth()->user();
            if ($user && $user->role === 'commercial') {
                $builder->where('deliveries.commercial_id', $user->id);
            }
        });
    }

    public function order() { return $this->belongsTo(Order::class)->withTrashed(); }
    public function client() { return $this->belongsTo(Client::class)->withTrashed(); }
    public function commercial() { return $this->belongsTo(User::class, 'commercial_id')->withTrashed(); }
    public function items() { return $this->hasMany(DeliveryItem::class); }

    public function isDraft(): bool { return $this->status === 'brouillon'; }
    public function displayNumber(): string { return $this->number ?? 'Brouillon #' . $this->id; }
}
