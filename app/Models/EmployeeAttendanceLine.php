<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAttendanceLine extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'attendance_header_id',
        'employee_id',
        'status',
        'day_value',
        'remarks',
    ];

    protected $casts = [
        'day_value' => 'decimal:2',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(EmployeeAttendanceHeader::class, 'attendance_header_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
