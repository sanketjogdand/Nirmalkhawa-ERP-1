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
        Schema::create('production_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained('production_batches')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('recipe_item_id')->nullable()->constrained('recipe_items')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('material_product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('planned_qty', 12, 3)->default(0);
            $table->decimal('actual_qty_used', 12, 3)->nullable();
            $table->string('uom', 20);
            $table->boolean('is_yield_base')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_inputs');
    }
};
