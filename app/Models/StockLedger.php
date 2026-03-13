<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLedger extends Model
{
    protected $fillable = [
        'variant_id','user_id','purchase_item_id','action_type',
        'quantity_change','stock_before','stock_after','reference_no','notes','transaction_date'
    ];

    public function variant()
    {
        return $this->belongsTo(ItemVariant::class,'variant_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class,'purchase_item_id');
    }
}
