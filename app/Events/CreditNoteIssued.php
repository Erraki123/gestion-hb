<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;

class CreditNoteIssued
{
    use Dispatchable;

    public function __construct(public Invoice $creditNote) {}
}
