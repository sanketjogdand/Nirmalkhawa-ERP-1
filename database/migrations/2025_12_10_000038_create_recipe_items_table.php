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
        Schema::create('recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('material_product_id')->constrained('products');
            $table->decimal('standard_qty', 15, 3);
            $table->string('uom', 20);
            $table->boolean('is_yield_base')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['recipe_id', 'material_product_id']);
            $table->index(['recipe_id', 'is_yield_base']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_items');
    }
};
