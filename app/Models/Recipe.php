<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recipe extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'output_product_id',
        'name',
        'version',
        'is_active',
        'notes',
        'output_qty',
        'output_uom',
    ];

    protected $casts = [
        'version' => 'integer',
        'is_active' => 'boolean',
        'output_qty' => 'decimal:3',
    ];

    public function outputProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'output_product_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $recipe) {
            if ($recipe->isForceDeleting()) {
                $recipe->items()->forceDelete();
            } else {
                $recipe->items()->delete();
            }
        });

        static::restoring(function (self $recipe) {
            $recipe->items()->withTrashed()->restore();
        });
    }
}
