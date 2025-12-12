<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RateChart extends Model
{
    protected $fillable = [
        'code',
        'milk_type',
        'base_rate',
        'base_fat',
        'base_snf',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'base_rate' => 'float',
        'base_fat' => 'float',
        'base_snf' => 'float',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function slabs(): HasMany
    {
        return $this->hasMany(RateChartSlab::class);
    }

    public function fatSlabs(): HasMany
    {
        return $this->slabs()->where('param_type', 'FAT');
    }

    public function snfSlabs(): HasMany
    {
        return $this->slabs()->where('param_type', 'SNF');
    }

    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(Center::class, 'center_rate_chart')
            ->withPivot(['effective_from', 'effective_to', 'is_active'])
            ->withTimestamps();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CenterRateChart::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForMilkType($query, string $milkType)
    {
        return $query->where('milk_type', $milkType);
    }

    public function scopeEffectiveOn($query, $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->whereNull('effective_from')->orWhere('effective_from', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
        });
    }
}
