<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\LogsActivity;
class Product extends Model
{
    use SoftDeletes , LogsActivity;

    protected $fillable = [
        'category_id', 'name', 'sku', 'description', 'unit', 'tax_rate',
        'purchase_price', 'sale_price', 'stock_quantity', 'min_stock',
        'image_path', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:4',
            'sale_price'     => 'decimal:4',
            'tax_rate'       => 'decimal:2',
            'stock_quantity' => 'decimal:2',
            'min_stock'      => 'decimal:2',
            'is_active'      => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    public function priceHistories()
    {
        return $this->hasMany(PriceHistory::class)->latest();
    }

    public function scopeLowStock(Builder $q): Builder
    {
        return $q->whereColumn('stock_quantity', '<=', 'min_stock');
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->min_stock;
    }

    public function margin(): float
    {
        return (float) $this->sale_price - (float) $this->purchase_price;
    }

    public function priceTtc(): float
    {
        return round((float) $this->sale_price * (1 + (float) $this->tax_rate / 100), 2);
    }
}
