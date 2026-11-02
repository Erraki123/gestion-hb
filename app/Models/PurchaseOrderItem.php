<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use LogsActivity;

    protected $fillable = [
        'purchase_order_id', 'product_id', 'name', 'sku', 'unit', 'quantity',
        'unit_cost', 'discount_percent', 'tax_rate', 'line_total_ht', 'received_quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:4',
            'discount_percent' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'line_total_ht' => 'decimal:2',
            'received_quantity' => 'decimal:2',
        ];
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function receipts()
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function remainingQuantity(): float
    {
        return max(0, (float) $this->quantity - (float) $this->received_quantity);
    }
}
