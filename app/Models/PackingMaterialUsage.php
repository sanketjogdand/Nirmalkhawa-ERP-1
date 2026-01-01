<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackingMaterialUsage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'packing_id',
        'packing_item_id',
        'pack_size_id',
        'material_product_id',
        'qty_used',
        'uom',
        'remarks',
    ];

    protected $casts = [
        'qty_used' => 'decimal:3',
    ];

    public function packing(): BelongsTo
    {
        return $this->belongsTo(Packing::class);
    }

    public function packingItem(): BelongsTo
    {
        return $this->belongsTo(PackingItem::class);
    }

    public function packSize(): BelongsTo
    {
        return $this->belongsTo(PackSize::class);
    }

    public function materialProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'material_product_id');
    }
}
