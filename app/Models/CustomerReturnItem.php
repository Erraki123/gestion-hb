<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerReturnItem extends Model
{
    protected $fillable = [
        'customer_return_id', 'invoice_item_id', 'product_id', 'name', 'sku', 'unit',
        'quantity', 'unit_price', 'discount_percent', 'tax_rate', 'line_total_ht', 'restock',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:4',
            'discount_percent' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'line_total_ht' => 'decimal:2',
            'restock' => 'boolean',
        ];
    }

    public function customerReturn()
    {
        return $this->belongsTo(CustomerReturn::class);
    }

    public function invoiceItem()
    {
        return $this->belongsTo(InvoiceItem::class)->withTrashed();
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
