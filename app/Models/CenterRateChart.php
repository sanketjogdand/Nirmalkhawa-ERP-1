<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CenterRateChart extends Model
{
    use SoftDeletes;

    protected $table = 'center_rate_chart';

    protected $fillable = [
        'center_id',
        'rate_chart_id',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function rateChart(): BelongsTo
    {
        return $this->belongsTo(RateChart::class);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->whereNull('effective_from')->orWhere('effective_from', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
        });
    }
}
