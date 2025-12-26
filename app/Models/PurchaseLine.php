<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseLine extends Model
{
    protected $fillable = [
        'purchase_id',
        'product_id',
        'description',
        'qty',
        'uom',
        'rate',
        'gst_rate_percent',
        'taxable_amount',
        'gst_amount',
        'line_total',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'rate' => 'decimal:3',
        'gst_rate_percent' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
