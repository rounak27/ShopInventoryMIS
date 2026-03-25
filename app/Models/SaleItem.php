<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'variant_id',
        'quantity',
        'price_per_unit',
        'total_price',
        'cost_price',
        'profit',
        'discount_amount',
    ];

    protected $casts = [
        'price_per_unit' => 'decimal:2',
        'total_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'profit' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    /**
     * The sale this item belongs to.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * The variant this item is selling.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class);
    }
}
