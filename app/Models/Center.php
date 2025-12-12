<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Center extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address',
        'state_id',
        'district_id',
        'taluka_id',
        'village_id',
        'contact_person',
        'mobile',
        'account_name',
        'account_number',
        'ifsc',
        'branch',
        'status',
    ];

    public function state() { return $this->belongsTo(State::class); }
    public function district() { return $this->belongsTo(District::class); }
    public function taluka() { return $this->belongsTo(Taluka::class); }
    public function village() { return $this->belongsTo(Village::class); }

    public function rateCharts(): BelongsToMany
    {
        return $this->belongsToMany(RateChart::class, 'center_rate_chart')
            ->withPivot(['effective_from', 'effective_to', 'is_active'])
            ->withTimestamps();
    }

    public function rateChartAssignments(): HasMany
    {
        return $this->hasMany(CenterRateChart::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }
}
