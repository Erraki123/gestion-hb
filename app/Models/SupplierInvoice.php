<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SupplierInvoice extends Model
{
    use LogsActivity;

    protected $fillable = [
        'supplier_id', 'purchase_order_id', 'supplier_number', 'invoice_date', 'due_date',
        'subtotal_ht', 'tax_amount', 'total_ttc', 'amount_paid', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal_ht' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class)->withTrashed();
    }

    public function lines()
    {
        return $this->hasMany(SupplierInvoiceLine::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function balance(): float
    {
        return round((float) $this->total_ttc - (float) $this->amount_paid, 2);
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['non_payee', 'partiellement_payee'], true)
            && $this->due_date && $this->due_date->lt(today());
    }

    public function displayNumber(): string
    {
        return $this->supplier_number ?: 'Facture fournisseur #' . $this->id;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['non_payee', 'partiellement_payee']);
    }
}
