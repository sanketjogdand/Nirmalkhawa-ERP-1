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
        Schema::create('pack_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('pack_qty', 15, 3);
            $table->string('pack_uom', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'pack_qty', 'pack_uom'], 'pack_sizes_unique_per_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pack_sizes');
    }
};
