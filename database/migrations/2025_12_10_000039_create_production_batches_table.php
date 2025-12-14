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
        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('output_product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('actual_output_qty', 12, 3);
            $table->string('output_uom', 20);
            $table->text('remarks')->nullable();
            $table->foreignId('yield_base_product_id')->nullable()->constrained('products')->cascadeOnUpdate()->nullOnDelete();
            $table->decimal('yield_base_actual_qty_used', 12, 3)->nullable();
            $table->decimal('yield_ratio', 12, 4)->nullable();
            $table->decimal('yield_pct', 12, 2)->nullable();
            $table->boolean('is_locked')->default(false);
            $table->foreignId('locked_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['date', 'output_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_batches');
    }
};
