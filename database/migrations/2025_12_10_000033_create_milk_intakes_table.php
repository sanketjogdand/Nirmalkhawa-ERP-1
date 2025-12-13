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
        Schema::create('milk_intakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('shift', 10); // MORNING, EVENING
            $table->string('milk_type', 2); // CM, BM
            $table->decimal('qty_ltr', 10, 2);
            $table->decimal('density_factor', 5, 3)->default(1.030);
            $table->decimal('qty_kg', 10, 3);
            $table->decimal('fat_pct', 5, 2);
            $table->decimal('snf_pct', 5, 2);
            $table->decimal('rate_per_ltr', 10, 2)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->decimal('kg_fat', 10, 3);
            $table->decimal('kg_snf', 10, 3);
            $table->string('rate_status', 12)->default('CALCULATED');
            $table->foreignId('manual_rate_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manual_rate_at')->nullable();
            $table->text('manual_rate_reason')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->index(['date', 'shift', 'milk_type']);
            $table->index(['center_id', 'date']);
        });
    }

    /**
    * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('milk_intakes');
    }
};
