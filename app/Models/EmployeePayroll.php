<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeePayroll extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'payroll_month',
        'total_days_in_month',
        'present_days',
        'basic_pay',
        'incentives_total',
        'advance_deduction',
        'other_additions',
        'other_deductions',
        'net_pay',
        'remarks',
        'is_locked',
        'locked_by',
        'locked_at',
        'created_by',
    ];

    protected $casts = [
        'present_days' => 'decimal:2',
        'basic_pay' => 'decimal:2',
        'incentives_total' => 'decimal:2',
        'advance_deduction' => 'decimal:2',
        'other_additions' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
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
