<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HnbWhatsAppOutboundMessage extends Model
{
    protected $table = 'hnb_whatsapp_outbound_messages';
    protected $guarded = ['id'];
    protected function casts(): array { return ['decided_at'=>'datetime','attempted_at'=>'datetime']; }
    public function actor() { return $this->belongsTo(User::class,'created_by')->withTrashed(); }
    public function director() { return $this->belongsTo(User::class,'decided_by')->withTrashed(); }
}
