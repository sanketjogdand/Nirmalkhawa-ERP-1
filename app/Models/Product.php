<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'uom',
        'hsn_code',
        'default_gst_rate',
        'category',
        'can_purchase',
        'can_produce',
        'can_consume',
        'can_sell',
        'can_stock',
        'is_active',
    ];

    protected $casts = [
        'default_gst_rate' => 'decimal:2',
        'can_purchase' => 'boolean',
        'can_produce' => 'boolean',
        'can_consume' => 'boolean',
        'can_sell' => 'boolean',
        'can_stock' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function ledgers(): HasMany
    {
        return $this->hasMany(StockLedger::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'output_product_id');
    }

    public function recipeItems(): HasManyThrough
    {
        return $this->hasManyThrough(RecipeItem::class, Recipe::class, 'output_product_id', 'recipe_id', 'id', 'id');
    }

    public function packSizes(): HasMany
    {
        return $this->hasMany(PackSize::class);
    }

    public function packInventories(): HasMany
    {
        return $this->hasMany(PackInventory::class);
    }

    public function packings(): HasMany
    {
        return $this->hasMany(Packing::class);
    }

    public function unpackings(): HasMany
    {
        return $this->hasMany(Unpacking::class);
    }
}
