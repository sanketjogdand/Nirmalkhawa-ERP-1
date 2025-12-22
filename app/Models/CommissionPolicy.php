<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionPolicy extends Model
{
    protected $fillable = [
        'code',
        'milk_type',
        'value',
        'is_active',
    ];

    protected $casts = [
        'value' => 'float',
        'is_active' => 'boolean',
    ];

    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(Center::class, 'center_commission_assignments')
            ->withPivot(['effective_from', 'effective_to', 'is_active'])
            ->withTimestamps();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CenterCommissionAssignment::class);
    }
}
