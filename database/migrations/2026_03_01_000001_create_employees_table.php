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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 50)->unique();
            $table->string('name', 255);
            $table->string('mobile', 20)->nullable();
            $table->date('joining_date')->nullable();
            $table->date('resignation_date')->nullable();
            $table->string('designation', 150)->nullable();
            $table->string('department', 150)->nullable();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('taluka_id')->nullable()->constrained('talukas')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->text('address_line')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['name']);
            $table->index(['mobile']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
