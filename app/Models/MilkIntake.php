<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class MilkIntake extends Model
{
    protected $fillable = [
        'center_id',
        'date',
        'shift',
        'milk_type',
        'qty_ltr',
        'density_factor',
        'qty_kg',
        'fat_pct',
        'snf_pct',
        'rate_per_ltr',
        'amount',
        'kg_fat',
        'kg_snf',
        'rate_status',
        'manual_rate_by',
        'manual_rate_at',
        'manual_rate_reason',
        'is_locked',
        'locked_by',
        'locked_at',
    ];

    protected $casts = [
        'date' => 'date',
        'qty_ltr' => 'decimal:2',
        'density_factor' => 'decimal:3',
        'qty_kg' => 'decimal:3',
        'fat_pct' => 'decimal:2',
        'snf_pct' => 'decimal:2',
        'rate_per_ltr' => 'decimal:2',
        'amount' => 'decimal:2',
        'kg_fat' => 'decimal:3',
        'kg_snf' => 'decimal:3',
        'manual_rate_at' => 'datetime',
        'locked_at' => 'datetime',
        'is_locked' => 'boolean',
    ];

    public const SHIFT_MORNING = 'MORNING';
    public const SHIFT_EVENING = 'EVENING';
    public const STATUS_CALCULATED = 'CALCULATED';
    public const STATUS_MANUAL = 'MANUAL';

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function manualRateUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manual_rate_by');
    }

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    public function scopeCalculated($query)
    {
        return $query->where('rate_status', self::STATUS_CALCULATED);
    }

    public static function computeMetrics(
        float $qtyLtr,
        float $densityFactor,
        float $fatPct,
        float $snfPct,
        ?float $ratePerLtr
    ): array {
        $qtyKg = round($qtyLtr * $densityFactor, 3);
        $kgFat = round($qtyKg * $fatPct / 100, 3);
        $kgSnf = round($qtyKg * $snfPct / 100, 3);
        $amount = $ratePerLtr !== null ? round($qtyLtr * $ratePerLtr, 2) : null;

        return [
            'qty_kg' => $qtyKg,
            'kg_fat' => $kgFat,
            'kg_snf' => $kgSnf,
            'amount' => $amount,
        ];
    }

    public function markManualRate(float $rate, ?string $reason, int $userId): void
    {
        $this->rate_per_ltr = $rate;
        $this->rate_status = self::STATUS_MANUAL;
        $this->manual_rate_by = $userId;
        $this->manual_rate_at = Carbon::now();
        $this->manual_rate_reason = $reason;

        $this->syncDerivedAmounts($rate);
    }

    public function syncDerivedAmounts(?float $ratePerLtr): void
    {
        $metrics = self::computeMetrics(
            (float) $this->qty_ltr,
            (float) $this->density_factor,
            (float) $this->fat_pct,
            (float) $this->snf_pct,
            $ratePerLtr,
        );

        $this->qty_kg = $metrics['qty_kg'];
        $this->kg_fat = $metrics['kg_fat'];
        $this->kg_snf = $metrics['kg_snf'];
        $this->amount = $metrics['amount'];
    }
}
