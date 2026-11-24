<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Taux de commission spécial d'un produit, dans une version de règle. */
class CommissionRuleProduct extends Model
{
    protected $fillable = ['rule_id', 'product_id', 'rate'];

    public function rule()    { return $this->belongsTo(CommissionRule::class, 'rule_id'); }
    public function product() { return $this->belongsTo(Product::class)->withTrashed(); }
}
