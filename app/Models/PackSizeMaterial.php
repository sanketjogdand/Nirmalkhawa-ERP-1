<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackSizeMaterial extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'pack_size_id',
        'material_product_id',
        'qty_per_pack',
        'uom',
        'sort_order',
    ];

    protected $casts = [
        'qty_per_pack' => 'decimal:3',
        'sort_order' => 'integer',
    ];

    public function packSize(): BelongsTo
    {
        return $this->belongsTo(PackSize::class);
    }

    public function materialProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'material_product_id');
    }
}
