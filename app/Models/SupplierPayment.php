<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'number', 'supplier_invoice_id', 'supplier_id', 'user_id', 'amount',
        'method', 'paid_at', 'reference', 'cheque_number', 'status', 'cancelled_at',
        'cancelled_by', 'cancel_reason', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    public function supplierInvoice()
    {
        return $this->belongsTo(SupplierInvoice::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function displayNumber(): string
    {
        return $this->number ?? 'PF-' . $this->id;
    }
}
