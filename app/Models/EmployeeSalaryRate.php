<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSalaryRate extends Model
{
    use SoftDeletes;

    public const TYPE_MONTHLY = 'MONTHLY';
    public const TYPE_DAILY = 'DAILY';

    protected $fillable = [
        'employee_id',
        'effective_from',
        'salary_type',
        'rate_amount',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'rate_amount' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
