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
        Schema::table('center_payments', function (Blueprint $table) {
            $table->string('payment_type', 10)->default('REGULAR')->after('payment_date');
            $table->index(['center_id', 'payment_date']);
            $table->index('payment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('center_payments', function (Blueprint $table) {
            $table->dropIndex(['center_id', 'payment_date']);
            $table->dropIndex(['payment_type']);
            $table->dropColumn('payment_type');
        });
    }
};
