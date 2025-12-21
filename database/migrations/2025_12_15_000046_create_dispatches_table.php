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
        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('dispatch_no', 30)->unique();
            $table->date('dispatch_date');
            $table->enum('delivery_mode', ['SELF_PICKUP', 'COMPANY_DELIVERY']);
            $table->string('vehicle_no', 50)->nullable();
            $table->string('driver_name', 150)->nullable();
            $table->string('remarks', 1000)->nullable();
            $table->enum('status', ['DRAFT', 'POSTED'])->default('DRAFT');
            $table->boolean('is_locked')->default(false);
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('locked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['dispatch_date', 'status']);
            $table->index('delivery_mode');
            $table->index('is_locked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispatches');
    }
};
