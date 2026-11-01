<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'product_id', 'name', 'sku', 'unit',
        'quantity', 'unit_price', 'discount_percent', 'tax_rate', 'line_total_ht',
    ];
}