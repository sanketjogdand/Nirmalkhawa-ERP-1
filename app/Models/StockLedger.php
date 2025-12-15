<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLedger extends Model
{
    use HasFactory;

    public const TYPE_OPENING = 'OPENING';
    public const TYPE_IN = 'IN';
    public const TYPE_OUT = 'OUT';
    public const TYPE_ADJ = 'ADJ';
    public const TYPE_PRODUCTION_IN = 'PRODUCTION_IN';
    public const TYPE_PRODUCTION_OUT = 'PRODUCTION_OUT';
    public const TYPE_TRANSFER = 'TRANSFER';
    public const TYPE_PACKING_OUT = 'PACKING_OUT';
    public const TYPE_UNPACKING_IN = 'UNPACKING_IN';

    protected $fillable = [
        'product_id',
        'txn_datetime',
        'txn_type',
        'is_increase',
        'qty',
        'uom',
        'rate',
        'reference_type',
        'reference_id',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'txn_datetime' => 'datetime',
        'qty' => 'decimal:3',
        'rate' => 'decimal:4',
        'is_increase' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSignedQtyAttribute(): float
    {
        $qty = (float) $this->qty;

        return $this->is_increase ? $qty : -$qty;
    }
}
