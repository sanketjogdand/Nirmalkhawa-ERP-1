<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettlementPeriodTemplate extends Model
{
    protected $fillable = [
        'name',
        'start_day',
        'end_day',
        'end_of_month',
        'is_active',
    ];

    protected $casts = [
        'end_of_month' => 'boolean',
        'is_active' => 'boolean',
    ];
}
