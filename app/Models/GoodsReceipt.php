<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
    use LogsActivity;

    protected $fillable = [
        'number', 'purchase_order_id', 'supplier_id', 'status', 'receipt_date',
        'supplier_ref', 'notes', 'validated_at', 'validated_by', 'cancelled_at',
        'cancelled_by', 'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
            'validated_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function items()
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by')->withTrashed();
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by')->withTrashed();
    }

    public function isDraft(): bool
    {
        return $this->status === 'brouillon';
    }

    public function displayNumber(): string
    {
        return $this->number ?? 'BR-' . $this->id;
    }
}
