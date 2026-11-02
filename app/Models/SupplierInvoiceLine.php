<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class SupplierInvoiceLine extends Model
{
    use LogsActivity;

    protected $fillable = ['supplier_invoice_id', 'tax_rate', 'base_ht', 'tax_amount'];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'base_ht' => 'decimal:2',
            'tax_amount' => 'decimal:2',
        ];
    }

    public function supplierInvoice()
    {
        return $this->belongsTo(SupplierInvoice::class);
    }
}
