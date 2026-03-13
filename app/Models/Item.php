<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = ['category_id', 'name', 'sku', 'brand', 'cost_price', 'selling_price', 'description', 'image_path', 'is_active'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ItemVariant::class);
    }
}
