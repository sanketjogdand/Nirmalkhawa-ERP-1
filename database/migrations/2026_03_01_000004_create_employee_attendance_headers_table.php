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
        Schema::create('employee_attendance_headers', function (Blueprint $table) {
            $table->id();
            $table->date('attendance_date')->unique();
            $table->string('remarks', 500)->nullable();
            $table->boolean('is_locked')->default(false);
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['attendance_date']);
            $table->index(['is_locked']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_attendance_lines');
        Schema::dropIfExists('employee_attendance_headers');
    }
};
