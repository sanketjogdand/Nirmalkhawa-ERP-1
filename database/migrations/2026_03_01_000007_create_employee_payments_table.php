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
        Schema::create('employee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('payment_date');
            $table->string('payment_type', 20);
            $table->decimal('amount', 12, 2);
            $table->string('payment_mode', 100)->nullable();
            $table->string('company_account', 150)->nullable();
            $table->string('remarks', 500)->nullable();
            $table->boolean('is_locked')->default(false);
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'payment_date']);
            $table->index(['payment_type']);
            $table->index(['is_locked']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_payments');
    }
};
