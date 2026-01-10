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
        Schema::table('general_expense_lines', function (Blueprint $table) {
            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();
            $table->string('vendor_name')->nullable();
            $table->string('vendor_invoice_no')->nullable();
            $table->date('vendor_invoice_date')->nullable();
            $table->string('vendor_gstin', 15)->nullable();

            $table->index('vendor_id');
            $table->index('vendor_invoice_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_expense_lines', function (Blueprint $table) {
            $table->dropIndex(['vendor_invoice_no']);
            $table->dropIndex(['vendor_id']);
            $table->dropForeign(['vendor_id']);
            $table->dropColumn([
                'vendor_id',
                'vendor_name',
                'vendor_invoice_no',
                'vendor_invoice_date',
                'vendor_gstin',
            ]);
        });
    }
};
