<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class HnbEmailMessage extends Model
{
    protected $fillable=['request_key','client_id','created_by','decided_by','recipient','subject','body','status','reason','decided_at','attempted_at','submitted_at'];
    protected $casts=['decided_at'=>'datetime','attempted_at'=>'datetime','submitted_at'=>'datetime'];
    public function actor() { return $this->belongsTo(User::class,'created_by')->withTrashed(); }
    public function client() { return $this->belongsTo(Client::class); }
}
