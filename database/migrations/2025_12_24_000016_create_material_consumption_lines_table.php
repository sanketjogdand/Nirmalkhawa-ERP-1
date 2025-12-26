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
        Schema::create('material_consumption_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_consumption_id')->constrained('material_consumptions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('qty', 15, 3);
            $table->string('uom', 30);
            $table->string('remarks', 1000)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('material_consumption_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_consumption_lines');
    }
};
