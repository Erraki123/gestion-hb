<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'product_id', 'name', 'sku', 'unit',
        'quantity', 'unit_price', 'discount_percent', 'tax_rate', 'line_total_ht',
    ];

    public function returnedQuantity(): float
    {
        return round((float) CustomerReturnItem::where('invoice_item_id', $this->id)
            ->whereHas('customerReturn', fn ($q) => $q->where('status', 'valide'))
            ->sum('quantity'), 2);
    }

    public function returnableQuantity(): float
    {
        return round((float) $this->quantity - $this->returnedQuantity(), 2);
    }
}
