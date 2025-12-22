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
        Schema::create('sales_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('sale_mode', ['BULK', 'PACK']);
            $table->decimal('rate_per_kg', 15, 3);
            $table->decimal('gst_rate_percent', 5, 2);
            $table->foreignId('dispatch_id')->nullable()->constrained('dispatches')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('dispatch_line_id')->nullable()->constrained('dispatch_lines')->cascadeOnUpdate()->nullOnDelete();
            $table->decimal('qty_bulk', 15, 3)->nullable();
            $table->string('uom', 20)->nullable();
            $table->foreignId('pack_size_id')->nullable()->constrained('pack_sizes')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedInteger('pack_count')->default(0);
            $table->decimal('computed_total_qty', 15, 3)->default(0);
            $table->decimal('pack_qty_snapshot', 15, 3)->nullable();
            $table->string('pack_uom', 20)->nullable();
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('gst_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['sales_invoice_id', 'sale_mode']);
            $table->index('dispatch_line_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_lines');
    }
};
