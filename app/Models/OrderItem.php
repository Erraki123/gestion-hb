<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'name', 'sku', 'unit',
        'quantity', 'unit_price', 'discount_percent', 'tax_rate', 'line_total_ht',
    ];

    public function order() { return $this->belongsTo(Order::class); }
    public function product() { return $this->belongsTo(Product::class)->withTrashed(); }
    public function deliveries() { return $this->hasMany(DeliveryItem::class); }

    public function deliveredQuantity(): float
    {
        return (float) $this->deliveries()
            ->join('deliveries', 'delivery_items.delivery_id', '=', 'deliveries.id')
            ->where('deliveries.status', 'valide')
            ->withoutGlobalScopes()
            ->sum('delivery_items.quantity');
    }

    public function remainingQuantity(): float
    {
        return max((float) $this->quantity - $this->deliveredQuantity(), 0);
    }
}