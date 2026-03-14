<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = ['supplier_id','supplier_name','created_by','po_reference','purchase_date','total_cost','notes','status'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function variant()
    {
        return $this->belongsTo(ItemVariant::class);
    }
    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class,'created_by');
    }
    public function items()
    {
        return $this->purchaseItems();
    }
}
