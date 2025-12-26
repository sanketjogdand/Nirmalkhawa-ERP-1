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
        Schema::create('grn_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('grns')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('received_qty', 15, 3);
            $table->string('uom', 30)->nullable();
            $table->string('remarks', 1000)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('grn_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grn_lines');
    }
};
