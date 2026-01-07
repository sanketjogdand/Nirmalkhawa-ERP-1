<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('payroll_month', 7);
            $table->unsignedInteger('total_days_in_month');
            $table->decimal('present_days', 6, 2)->default(0);
            $table->decimal('basic_pay', 12, 2)->default(0);
            $table->decimal('incentives_total', 12, 2)->default(0);
            $table->decimal('advance_deduction', 12, 2)->default(0);
            $table->decimal('other_additions', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->string('remarks', 500)->nullable();
            $table->boolean('is_locked')->default(false);
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['employee_id', 'payroll_month']);
            $table->index(['payroll_month']);
            $table->index(['is_locked']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_payrolls');
    }
};
