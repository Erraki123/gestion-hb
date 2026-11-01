<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceHistory extends Model
{
    protected $fillable = [
        'product_id', 'user_id',
        'old_sale_price', 'new_sale_price',
        'old_purchase_price', 'new_purchase_price',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
