<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionInput extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'production_batch_id',
        'recipe_item_id',
        'material_product_id',
        'planned_qty',
        'actual_qty_used',
        'uom',
        'is_yield_base',
    ];

    protected $casts = [
        'planned_qty' => 'decimal:3',
        'actual_qty_used' => 'decimal:3',
        'is_yield_base' => 'boolean',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function recipeItem(): BelongsTo
    {
        return $this->belongsTo(RecipeItem::class);
    }

    public function materialProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'material_product_id');
    }
}
