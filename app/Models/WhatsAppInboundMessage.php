<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppInboundMessage extends Model
{
    protected $table = 'whatsapp_inbound_messages';

    protected $fillable = ['provider_message_id', 'client_id', 'sender', 'body', 'received_at', 'read_at'];
    protected function casts(): array
    {
        return ['received_at' => 'datetime', 'read_at' => 'datetime'];
    }
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
