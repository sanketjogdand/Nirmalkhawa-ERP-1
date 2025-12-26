<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialConsumption extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'consumption_date',
        'consumption_type',
        'remarks',
        'is_locked',
        'locked_by',
        'locked_at',
        'created_by',
    ];

    protected $casts = [
        'consumption_date' => 'date',
        'locked_at' => 'datetime',
        'is_locked' => 'boolean',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(MaterialConsumptionLine::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
}
