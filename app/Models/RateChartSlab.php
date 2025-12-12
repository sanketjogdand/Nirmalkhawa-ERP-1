<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RateChartSlab extends Model
{
    protected $fillable = [
        'rate_chart_id',
        'param_type',
        'start_val',
        'end_val',
        'step',
        'rate_per_step',
    ];

    protected $casts = [
        'start_val' => 'float',
        'end_val' => 'float',
        'step' => 'float',
        'rate_per_step' => 'float',
        'priority' => 'integer',
    ];

    public function rateChart(): BelongsTo
    {
        return $this->belongsTo(RateChart::class);
    }
}
