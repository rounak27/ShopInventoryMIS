<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemVariant extends Model
{
    protected $fillable = ['item_id','size','color','current_stock','reorder_level','barcode','is_active'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class,'variant_id');
    }

    public function stockLedgers()
    {
        return $this->hasMany(StockLedger::class,'variant_id');
    }
}
