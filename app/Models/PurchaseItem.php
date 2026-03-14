<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = ['purchase_id','variant_id','quantity','cost_price_per_unit','total_cost'];
    public $timestamps = false;

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function variant()
    {
        return $this->belongsTo(ItemVariant::class,'variant_id');
    }

    public function stockLedgers()
    {
        return $this->hasMany(StockLedger::class,'purchase_item_id');
    }
}
