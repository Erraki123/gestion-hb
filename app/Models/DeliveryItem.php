<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryItem extends Model
{
    protected $fillable = [
        'delivery_id', 'order_item_id', 'product_id', 'name', 'sku', 'unit', 'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
        ];
    }

    public function delivery() { return $this->belongsTo(Delivery::class); }
    public function orderItem() { return $this->belongsTo(OrderItem::class)->withTrashed(); }
    public function product() { return $this->belongsTo(Product::class)->withTrashed(); }
}
