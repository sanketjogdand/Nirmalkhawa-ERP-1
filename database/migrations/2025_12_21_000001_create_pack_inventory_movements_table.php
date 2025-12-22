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
        Schema::create('pack_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('pack_size_id')->constrained('pack_sizes')->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('pack_count_change');
            $table->decimal('pack_qty_snapshot', 15, 3)->nullable();
            $table->string('pack_uom', 20)->nullable();
            $table->string('direction', 10)->comment('OUT/IN');
            $table->string('remarks', 1000)->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'pack_size_id']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pack_inventory_movements');
    }
};
