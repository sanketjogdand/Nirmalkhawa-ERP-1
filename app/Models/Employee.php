<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_code',
        'name',
        'mobile',
        'joining_date',
        'resignation_date',
        'designation',
        'department',
        'state_id',
        'district_id',
        'taluka_id',
        'village_id',
        'address_line',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'resignation_date' => 'date',
    ];

    public function employmentPeriods(): HasMany
    {
        return $this->hasMany(EmployeeEmploymentPeriod::class);
    }

    public function salaryRates(): HasMany
    {
        return $this->hasMany(EmployeeSalaryRate::class);
    }

    public function attendanceLines(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceLine::class);
    }

    public function incentives(): HasMany
    {
        return $this->hasMany(EmployeeIncentive::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(EmployeePayment::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(EmployeePayroll::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function taluka(): BelongsTo
    {
        return $this->belongsTo(Taluka::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
