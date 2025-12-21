<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackInventoryMovement extends Model
{
    use HasFactory;

    public const DIR_OUT = 'OUT';
    public const DIR_IN = 'IN';

    protected $fillable = [
        'product_id',
        'pack_size_id',
        'pack_count_change',
        'pack_qty_snapshot',
        'pack_uom',
        'direction',
        'remarks',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'pack_count_change' => 'integer',
        'pack_qty_snapshot' => 'decimal:3',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function packSize(): BelongsTo
    {
        return $this->belongsTo(PackSize::class);
    }
}
