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
        Schema::create('rate_chart_slabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rate_chart_id')->constrained()->cascadeOnDelete();
            $table->string('param_type', 3); // FAT or SNF
            $table->decimal('start_val', 5, 2);
            $table->decimal('end_val', 5, 2);
            $table->decimal('step', 4, 2)->default(0.10);
            $table->decimal('rate_per_step', 10, 2);
            $table->timestamps();

            $table->index(['rate_chart_id', 'param_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_chart_slabs');
    }
};
