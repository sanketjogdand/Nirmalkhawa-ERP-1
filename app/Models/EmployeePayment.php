<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeePayment extends Model
{
    use SoftDeletes;

    public const TYPE_ADVANCE = 'ADVANCE';
    public const TYPE_SALARY = 'SALARY';
    public const TYPE_OTHER = 'OTHER';

    protected $fillable = [
        'employee_id',
        'payment_date',
        'payment_type',
        'amount',
        'payment_mode',
        'company_account',
        'remarks',
        'is_locked',
        'locked_by',
        'locked_at',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
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
