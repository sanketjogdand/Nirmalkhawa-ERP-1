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
        Schema::create('general_expense_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('general_expense_id')->constrained('general_expenses')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('expense_categories')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('description', 500)->nullable();
            $table->decimal('qty', 12, 3)->default(1);
            $table->decimal('rate', 12, 2)->nullable();
            $table->decimal('taxable_amount', 12, 2);
            $table->decimal('gst_rate', 5, 2)->nullable();
            $table->decimal('gst_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->foreignId('place_of_supply_state_id')->nullable()->constrained('states')->cascadeOnUpdate()->nullOnDelete();
            $table->boolean('is_rcm_applicable')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['general_expense_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_expense_lines');
    }
};
