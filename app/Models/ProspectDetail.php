<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProspectDetail extends Model
{
    protected $fillable = ['client_id','category','stage','source','next_follow_up_at','do_not_contact'];
    protected function casts(): array { return ['next_follow_up_at'=>'datetime','do_not_contact'=>'boolean']; }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
}
