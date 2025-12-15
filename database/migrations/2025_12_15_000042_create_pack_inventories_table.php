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
        Schema::create('pack_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('pack_size_id')->constrained('pack_sizes')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedInteger('pack_count')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'pack_size_id'], 'pack_inventory_unique_per_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pack_inventories');
    }
};
