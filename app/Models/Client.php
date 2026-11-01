<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\LogsActivity;
class Client extends Model
{
    use SoftDeletes , LogsActivity;

    protected $fillable = [
        'commercial_id', 'name', 'company', 'phone', 'email',
        'address', 'city', 'ice', 'status', 'comment',
    ];

    // ⭐ Sécurité côté serveur : le commercial ne voit que ses clients
    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $builder) {
            $user = auth()->user();

            if ($user && $user->role === 'commercial') {
                $builder->where('clients.commercial_id', $user->id);
            }
        });
    }

    public function commercial(): BelongsTo
    {
        return $this->belongsTo(User::class, 'commercial_id')->withTrashed();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ClientNote::class)->latest();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ClientActivity::class)->latest('happened_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ClientAttachment::class)->latest();
    }
}
