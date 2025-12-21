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
        Schema::create('dispatch_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->constrained('dispatches')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('sale_mode', ['BULK', 'PACK']);
            $table->decimal('qty_bulk', 15, 3)->nullable();
            $table->string('uom', 20)->nullable();
            $table->foreignId('pack_size_id')->nullable()->constrained('pack_sizes')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedInteger('pack_count')->default(0);
            $table->decimal('computed_total_qty', 15, 3)->default(0);
            $table->decimal('pack_qty_snapshot', 15, 3)->nullable();
            $table->string('pack_uom', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['dispatch_id', 'sale_mode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispatch_lines');
    }
};
