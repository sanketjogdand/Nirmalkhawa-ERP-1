<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecipeItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'recipe_id',
        'material_product_id',
        'standard_qty',
        'uom',
        'is_yield_base',
    ];

    protected $casts = [
        'standard_qty' => 'decimal:3',
        'is_yield_base' => 'boolean',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function materialProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'material_product_id');
    }
}
