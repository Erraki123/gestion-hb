<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientActivity extends Model
{
    protected $fillable = ['client_id', 'user_id', 'type', 'description', 'happened_at'];

    protected function casts(): array
    {
        return ['happened_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
