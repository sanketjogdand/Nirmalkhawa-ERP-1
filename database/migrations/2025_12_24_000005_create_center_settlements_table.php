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
        Schema::create('center_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->date('period_from');
            $table->date('period_to');
            $table->string('settlement_no', 50)->unique();
            $table->enum('status', ['DRAFT', 'FINAL'])->default('DRAFT');
            $table->decimal('total_qty_ltr', 15, 3)->default(0);
            $table->decimal('gross_amount_total', 15, 2)->default(0);
            $table->decimal('commission_total', 15, 2)->default(0);
            $table->decimal('net_total', 15, 2)->default(0);
            $table->decimal('cm_qty_ltr', 15, 3)->default(0);
            $table->decimal('cm_gross_amount', 15, 2)->default(0);
            $table->decimal('cm_commission', 15, 2)->default(0);
            $table->decimal('cm_net', 15, 2)->default(0);
            $table->decimal('bm_qty_ltr', 15, 3)->default(0);
            $table->decimal('bm_gross_amount', 15, 2)->default(0);
            $table->decimal('bm_commission', 15, 2)->default(0);
            $table->decimal('bm_net', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['center_id', 'period_from', 'period_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('center_settlements');
    }
};
