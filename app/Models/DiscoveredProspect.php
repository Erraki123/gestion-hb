<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DiscoveredProspect extends Model {
    protected $fillable = ['source','source_id','company','city','category','phone','email','website','source_url','score','status','client_id','discovered_at'];
    protected function casts(): array { return ['discovered_at'=>'datetime']; }
}
