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
        Schema::table('center_settlements', function (Blueprint $table) {
            $table->decimal('incentive_amount', 12, 2)->default(0);
            $table->decimal('advance_deducted', 12, 2)->default(0);
            $table->decimal('short_adjustment', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tds_amount', 12, 2)->default(0);
            $table->text('remarks')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('center_settlements', function (Blueprint $table) {
            $table->dropColumn([
                'incentive_amount',
                'advance_deducted',
                'short_adjustment',
                'other_deductions',
                'discount_amount',
                'tds_amount',
                'remarks',
            ]);
        });
    }
};
