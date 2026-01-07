<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\EmployeeAttendanceHeader;
use App\Models\EmployeeAttendanceLine;
use App\Models\EmployeeEmploymentPeriod;
use App\Models\EmployeeIncentive;
use App\Models\EmployeePayment;
use App\Models\EmployeePayroll;
use App\Models\EmployeeSalaryRate;
use App\Services\EmployeeBalanceService;
use App\Services\EmploymentService;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_employment_period_overlap_validation(): void
    {
        $employee = Employee::create([
            'employee_code' => 'EMP001',
            'name' => 'Test Employee',
        ]);

        EmployeeEmploymentPeriod::create([
            'employee_id' => $employee->id,
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31',
        ]);

        $service = new EmploymentService();

        $this->expectException(\RuntimeException::class);
        $service->assertNoOverlap($employee->id, '2025-01-15', '2025-02-10');
    }

    public function test_employment_service_checks_active_dates(): void
    {
        $employee = Employee::create([
            'employee_code' => 'EMP002',
            'name' => 'Second Employee',
        ]);

        EmployeeEmploymentPeriod::create([
            'employee_id' => $employee->id,
            'start_date' => '2025-03-01',
            'end_date' => '2025-03-31',
        ]);

        $service = new EmploymentService();

        $this->assertTrue($service->isEmployedOn($employee->id, '2025-03-10'));
        $this->assertFalse($service->isEmployedOn($employee->id, '2025-04-01'));
    }

    public function test_payroll_segments_and_calculation(): void
    {
        $employee = Employee::create([
            'employee_code' => 'EMP003',
            'name' => 'Payroll Employee',
        ]);

        EmployeeEmploymentPeriod::create([
            'employee_id' => $employee->id,
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
        ]);

        EmployeeSalaryRate::create([
            'employee_id' => $employee->id,
            'effective_from' => '2025-02-01',
            'salary_type' => EmployeeSalaryRate::TYPE_MONTHLY,
            'rate_amount' => 2800,
        ]);

        EmployeeSalaryRate::create([
            'employee_id' => $employee->id,
            'effective_from' => '2025-02-15',
            'salary_type' => EmployeeSalaryRate::TYPE_MONTHLY,
            'rate_amount' => 5600,
        ]);

        $header1 = EmployeeAttendanceHeader::create([
            'attendance_date' => '2025-02-10',
        ]);
        EmployeeAttendanceLine::create([
            'attendance_header_id' => $header1->id,
            'employee_id' => $employee->id,
            'status' => 'P',
            'day_value' => 1,
        ]);

        $header2 = EmployeeAttendanceHeader::create([
            'attendance_date' => '2025-02-16',
        ]);
        EmployeeAttendanceLine::create([
            'attendance_header_id' => $header2->id,
            'employee_id' => $employee->id,
            'status' => 'P',
            'day_value' => 1,
        ]);

        EmployeeIncentive::create([
            'employee_id' => $employee->id,
            'incentive_date' => '2025-02-20',
            'amount' => 50,
        ]);

        $service = app(PayrollService::class);
        $computed = $service->recomputePayroll($employee->id, '2025-02');

        $this->assertCount(2, $computed['segments']);
        $this->assertEquals(2, $computed['present_days']);
        $this->assertEquals(300.0, $computed['basic_pay']);
        $this->assertEquals(50.0, $computed['incentives_total']);
        $this->assertEquals(350.0, $computed['net_pay']);
    }

    public function test_advance_outstanding_and_deduction_validation(): void
    {
        $employee = Employee::create([
            'employee_code' => 'EMP004',
            'name' => 'Advance Employee',
        ]);

        EmployeeEmploymentPeriod::create([
            'employee_id' => $employee->id,
            'start_date' => '2025-01-01',
            'end_date' => null,
        ]);

        EmployeeSalaryRate::create([
            'employee_id' => $employee->id,
            'effective_from' => '2025-01-01',
            'salary_type' => EmployeeSalaryRate::TYPE_DAILY,
            'rate_amount' => 100,
        ]);

        $header = EmployeeAttendanceHeader::create([
            'attendance_date' => '2025-01-05',
        ]);
        EmployeeAttendanceLine::create([
            'attendance_header_id' => $header->id,
            'employee_id' => $employee->id,
            'status' => 'P',
            'day_value' => 1,
        ]);

        EmployeePayment::create([
            'employee_id' => $employee->id,
            'payment_date' => '2025-01-03',
            'payment_type' => EmployeePayment::TYPE_ADVANCE,
            'amount' => 500,
        ]);

        EmployeePayroll::create([
            'employee_id' => $employee->id,
            'payroll_month' => '2024-12',
            'total_days_in_month' => 31,
            'present_days' => 0,
            'basic_pay' => 0,
            'incentives_total' => 0,
            'advance_deduction' => 200,
            'other_additions' => 0,
            'other_deductions' => 0,
            'net_pay' => 0,
        ]);

        $balanceService = new EmployeeBalanceService();
        $this->assertEquals(300.0, $balanceService->advanceOutstanding($employee->id));

        $service = app(PayrollService::class);

        $this->expectException(\RuntimeException::class);
        $service->recomputePayroll($employee->id, '2025-01', 400, 0, 0);
    }
}
