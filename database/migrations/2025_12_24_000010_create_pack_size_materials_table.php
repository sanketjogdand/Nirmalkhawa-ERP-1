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
        Schema::create('pack_size_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pack_size_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_product_id')->constrained('products');
            $table->decimal('qty_per_pack', 12, 3);
            $table->string('uom', 20)->nullable();
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['pack_size_id', 'material_product_id', 'deleted_at'], 'pack_size_materials_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pack_size_materials');
    }
};
