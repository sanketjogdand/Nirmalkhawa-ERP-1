<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockAdjustmentLine extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const DIRECTION_IN = 'IN';
    public const DIRECTION_OUT = 'OUT';

    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'direction',
        'qty',
        'uom',
        'remarks',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
    ];

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
