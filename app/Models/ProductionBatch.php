<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionBatch extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'date',
        'output_product_id',
        'recipe_id',
        'actual_output_qty',
        'output_uom',
        'remarks',
        'yield_base_product_id',
        'yield_base_actual_qty_used',
        'yield_ratio',
        'yield_pct',
        'is_locked',
        'locked_by',
        'locked_at',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'actual_output_qty' => 'decimal:3',
        'yield_base_actual_qty_used' => 'decimal:3',
        'yield_ratio' => 'decimal:4',
        'yield_pct' => 'decimal:2',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function outputProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'output_product_id');
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function inputs(): HasMany
    {
        return $this->hasMany(ProductionInput::class);
    }

    public function yieldBaseProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'yield_base_product_id');
    }

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $batch) {
            if ($batch->isForceDeleting()) {
                $batch->inputs()->forceDelete();
            } else {
                $batch->inputs()->delete();
            }
        });

        static::restoring(function (self $batch) {
            $batch->inputs()->withTrashed()->restore();
        });
    }
}
