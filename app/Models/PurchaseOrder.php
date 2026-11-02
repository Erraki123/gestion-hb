<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use LogsActivity;

    protected $fillable = [
        'number', 'supplier_id', 'status', 'order_date', 'expected_date',
        'subtotal_ht', 'tax_amount', 'total_ttc', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_date' => 'date',
            'subtotal_ht' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_ttc' => 'decimal:2',
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function receipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'brouillon';
    }

    public function displayNumber(): string
    {
        return $this->number ?? 'Brouillon #' . $this->id;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['brouillon', 'commande', 'recue_partielle']);
    }
}
