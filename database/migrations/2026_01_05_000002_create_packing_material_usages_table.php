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
        Schema::create('packing_material_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packing_id')->constrained('packings')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('packing_item_id')->nullable()->constrained('packing_items')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('pack_size_id')->constrained('pack_sizes')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('material_product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('qty_used', 15, 3);
            $table->string('uom', 20)->nullable();
            $table->string('remarks', 1000)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('packing_id');
            $table->index('material_product_id');
            $table->index('pack_size_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_material_usages');
    }
};
