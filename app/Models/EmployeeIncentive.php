<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeIncentive extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'incentive_date',
        'incentive_type',
        'amount',
        'remarks',
        'is_locked',
        'locked_by',
        'locked_at',
        'created_by',
    ];

    protected $casts = [
        'incentive_date' => 'date',
        'amount' => 'decimal:2',
        'locked_at' => 'datetime',
        'is_locked' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
