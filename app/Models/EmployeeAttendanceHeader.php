<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAttendanceHeader extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'attendance_date',
        'remarks',
        'is_locked',
        'locked_by',
        'locked_at',
        'created_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'locked_at' => 'datetime',
        'is_locked' => 'boolean',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceLine::class, 'attendance_header_id');
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
