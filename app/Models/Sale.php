<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'customer_name',
        'customer_pan',
        'bill_number',
        'fiscal_year',
        'created_by',
        'sub_total',
        'discount_amount',
        'taxable_amount',
        'vat',
        'grand_total',
        'payment_method',
        'status',
        'sale_date',
        'printed_at',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'printed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'sub_total' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'vat' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    /**
     * The user who created this sale.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Sale items belonging to this sale.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
