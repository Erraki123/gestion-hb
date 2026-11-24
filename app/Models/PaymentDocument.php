<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentDocument extends Model
{
    protected $fillable = [
        'payment_id', 'invoice_id', 'kind', 'path', 'original_name', 'mime', 'size', 'uploaded_by',
    ];

    public function payment()  { return $this->belongsTo(Payment::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by')->withTrashed(); }

    public function label(): string
    {
        return ($this->kind === 'cheque' ? 'Scan du chèque' : 'Document') . ' · ' . $this->original_name;
    }
}
