<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class GoodsReceiptItem extends Model
{
    use LogsActivity;

    protected $fillable = [
        'goods_receipt_id', 'purchase_order_item_id', 'product_id', 'name', 'sku',
        'unit', 'quantity', 'net_unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'net_unit_cost' => 'decimal:4',
        ];
    }

    public function goodsReceipt()
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
