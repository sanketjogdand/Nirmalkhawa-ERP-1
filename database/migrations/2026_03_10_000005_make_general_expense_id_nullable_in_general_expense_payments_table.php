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
        Schema::table('general_expense_payments', function (Blueprint $table) {
            $table->dropForeign(['general_expense_id']);
            $table->unsignedBigInteger('general_expense_id')->nullable()->change();
            $table->foreign('general_expense_id')
                ->references('id')
                ->on('general_expenses')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_expense_payments', function (Blueprint $table) {
            $table->dropForeign(['general_expense_id']);
            $table->unsignedBigInteger('general_expense_id')->nullable(false)->change();
            $table->foreign('general_expense_id')
                ->references('id')
                ->on('general_expenses')
                ->cascadeOnDelete();
        });
    }
};
