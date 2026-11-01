<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    protected $fillable = [
        'quote_id', 'product_id', 'name', 'sku', 'unit',
        'quantity', 'unit_price', 'discount_percent', 'tax_rate', 'line_total_ht',
    ];
}