<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CenterSettlement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'center_id',
        'period_from',
        'period_to',
        'total_qty_ltr',
        'gross_amount_total',
        'commission_total',
        'net_total',
        'cm_qty_ltr',
        'cm_gross_amount',
        'cm_commission',
        'cm_net',
        'bm_qty_ltr',
        'bm_gross_amount',
        'bm_commission',
        'bm_net',
        'notes',
        'is_locked',
        'locked_by',
        'locked_at',
        'created_by',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'locked_at' => 'datetime',
        'is_locked' => 'boolean',
        'total_qty_ltr' => 'decimal:3',
        'gross_amount_total' => 'decimal:2',
        'commission_total' => 'decimal:2',
        'net_total' => 'decimal:2',
        'cm_qty_ltr' => 'decimal:3',
        'cm_gross_amount' => 'decimal:2',
        'cm_commission' => 'decimal:2',
        'cm_net' => 'decimal:2',
        'bm_qty_ltr' => 'decimal:3',
        'bm_gross_amount' => 'decimal:2',
        'bm_commission' => 'decimal:2',
        'bm_net' => 'decimal:2',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function milkIntakes(): HasMany
    {
        return $this->hasMany(MilkIntake::class, 'center_settlement_id');
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
