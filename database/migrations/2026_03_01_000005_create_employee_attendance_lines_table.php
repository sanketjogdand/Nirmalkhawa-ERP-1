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
        Schema::create('employee_attendance_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_header_id')->constrained('employee_attendance_headers')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('status', 1);
            $table->decimal('day_value', 4, 2)->default(0);
            $table->string('remarks', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['attendance_header_id', 'employee_id'], 'emp_att_line_header_employee_unique');
            $table->index(['employee_id']);
            $table->index(['attendance_header_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_attendance_lines');
    }
};
