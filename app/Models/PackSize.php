<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackSize extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'pack_qty',
        'pack_uom',
        'is_active',
    ];

    protected $casts = [
        'pack_qty' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(PackInventory::class);
    }

    public function packingItems(): HasMany
    {
        return $this->hasMany(PackingItem::class);
    }

    public function unpackingItems(): HasMany
    {
        return $this->hasMany(UnpackingItem::class);
    }

    public function packMaterials(): HasMany
    {
        return $this->hasMany(PackSizeMaterial::class);
    }

    public function dispatchLines(): HasMany
    {
        return $this->hasMany(DispatchLine::class);
    }
}
