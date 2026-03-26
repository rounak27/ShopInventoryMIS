<?php

namespace App\Models;

use App\Helpers\BarcodeHelper;
use Illuminate\Database\Eloquent\Model;

class ItemVariant extends Model
{
    protected $fillable = ['item_id','size','color','current_stock','reorder_level','barcode','is_active'];

    protected static function booted(): void
    {
        static::created(function (ItemVariant $variant): void {
            if (!empty($variant->barcode)) {
                return;
            }

            $variant->barcode = BarcodeHelper::generate($variant->id);
            $variant->saveQuietly();
        });
    }

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
