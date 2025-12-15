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
        Schema::create('unpackings', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('total_bulk_qty', 15, 3);
            $table->string('remarks', 1000)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('unpacking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unpacking_id')->constrained('unpackings')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('pack_size_id')->constrained('pack_sizes')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedInteger('pack_count');
            $table->decimal('pack_qty_snapshot', 15, 3);
            $table->string('pack_uom', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unpacking_items');
        Schema::dropIfExists('unpackings');
    }
};
