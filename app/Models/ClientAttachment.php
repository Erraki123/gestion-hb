<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientAttachment extends Model
{
    use LogsActivity;

    protected array $logExclude = ['path'];

    protected $fillable = [
        'client_id', 'user_id', 'original_name', 'path', 'mime_type', 'size',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class); // le Global Scope s'applique ici aussi
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}
