<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'name', 'company', 'phone', 'email', 'address', 'city', 'ice',
        'payment_days', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'payment_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class)->latest('id');
    }

    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class)->latest('id');
    }

    public function invoices()
    {
        return $this->hasMany(SupplierInvoice::class)->latest('id');
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class)->latest('id');
    }

    public function displayNumber(): string
    {
        return $this->name;
    }
}
